<?php

declare(strict_types=1);

namespace App\Services\Importers;

use App\Models\TileSet;
use Illuminate\Support\Facades\Storage;

class LaxLegacyImporter implements ImporterInterface
{
    private array $tilesetMapping = [];
    private string $tilesetDirectory;

    public function __construct(string $tilesetDirectory = 'tilesets')
    {
        $this->tilesetDirectory = $tilesetDirectory;
    }

    /**
     * Set the tileset directory for this import.
     */
    public function setTilesetDirectory(string $directory): self
    {
        $this->tilesetDirectory = $directory;
        return $this;
    }

    /**
     * Get the current tileset directory.
     */
    public function getTilesetDirectory(): string
    {
        return $this->tilesetDirectory;
    }

    /**
     * Parse a legacy JavaScript map file and return structured data.
     */
    public function parse(string $filePath): array
    {
        // Try to read from Laravel storage first, then fallback to filesystem
        if (Storage::exists($filePath)) {
            $content = Storage::get($filePath);
        } elseif (file_exists($filePath)) {
            $content = file_get_contents($filePath);
        } else {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        if ($content === false) {
            throw new \InvalidArgumentException("Failed to read file: {$filePath}");
        }

        return $this->parseString($content, $filePath);
    }

    /**
     * Parse raw JavaScript data string and return structured data.
     */
    public function parseString(string $data, string $originalFilePath = ''): array
    {
        // Parse the JavaScript variables
        $mapData = $this->parseJavaScriptVariables($data);
        
        // Try to parse field type data if available
        $fieldTypeData = $this->parseFieldTypeFile($originalFilePath);
        
        // Convert to our standard format
        return $this->convertToStandardFormat($mapData, $fieldTypeData);
    }

    /**
     * Check if this importer can handle the given file.
     */
    public function canHandle(string $filePath): bool
    {
        // Check file extension
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->getSupportedExtensions())) {
            return false;
        }

        // Try to peek at the file content to verify it's legacy JavaScript format
        try {
            if (Storage::exists($filePath)) {
                $content = Storage::get($filePath);
            } elseif (file_exists($filePath)) {
                $content = file_get_contents($filePath);
            } else {
                return false;
            }

            if ($content === false) {
                return false;
            }

            // Check for legacy format markers
            $sample = substr($content, 0, 1000);
            return strpos($sample, 'var name =') !== false && 
                   strpos($sample, 'var width =') !== false &&
                   strpos($sample, 'field_bg') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the supported file extensions for this importer.
     */
    public function getSupportedExtensions(): array
    {
        return ['js'];
    }

    /**
     * Get a human-readable name for this importer.
     */
    public function getName(): string
    {
        return 'LAX Legacy Map Importer';
    }

    /**
     * Get a description of what this importer handles.
     */
    public function getDescription(): string
    {
        return 'Imports legacy JavaScript map files (field_bg, field_layer1, etc.) and field type files (_ft.js)';
    }

    /**
     * Parse JavaScript variables from the content.
     */
    private function parseJavaScriptVariables(string $content): array
    {
        $mapData = [
            'name' => '',
            'width' => 0,
            'height' => 0,
            'main_bg' => '',
            'external_creator' => null,
            'layers' => []
        ];

        // Parse basic variables
        if (preg_match("/var name = '([^']+)'/", $content, $matches)) {
            $mapData['name'] = $matches[1];
        }

        if (preg_match('/var width = (\d+);/', $content, $matches)) {
            $mapData['width'] = (int) $matches[1];
        }

        if (preg_match('/var height = (\d+);/', $content, $matches)) {
            $mapData['height'] = (int) $matches[1];
        }

        if (preg_match("/var main_bg = '([^']+)'/", $content, $matches)) {
            $mapData['main_bg'] = $matches[1];
        }

        // Parse external creator from comment
        if (preg_match('/\/\/ Editor: (.+)$/m', $content, $matches)) {
            $mapData['external_creator'] = trim($matches[1]);
        }

        // Parse layer arrays
        $layerNames = ['field_bg', 'field_layer1', 'field_layer2', 'field_layer4', 'field_layer5'];
        
        foreach ($layerNames as $layerName) {
            $mapData['layers'][$layerName] = $this->parseLayerArray($content, $layerName, $mapData['width'], $mapData['height']);
        }

        return $mapData;
    }

    /**
     * Parse field type file if it exists.
     */
    private function parseFieldTypeFile(string $originalFilePath): ?array
    {
        if (empty($originalFilePath)) {
            return null;
        }

        // Construct the field type file path
        $pathInfo = pathinfo($originalFilePath);
        $fieldTypeFilePath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_ft.js';

        // Try to read the field type file
        $content = null;
        if (Storage::exists($fieldTypeFilePath)) {
            $content = Storage::get($fieldTypeFilePath);
        } elseif (file_exists($fieldTypeFilePath)) {
            $content = file_get_contents($fieldTypeFilePath);
        }

        if ($content === false || $content === null) {
            return null; // Field type file doesn't exist
        }

        return $this->parseFieldTypeContent($content);
    }

    /**
     * Parse field type content from the _ft.js file.
     */
    private function parseFieldTypeContent(string $content): array
    {
        $fieldTypeData = [
            'default_x' => 10,
            'default_y' => 10,
            'field_types' => []
        ];

        // Parse default coordinates
        if (preg_match('/var map_default_x = (\d+);/', $content, $matches)) {
            $fieldTypeData['default_x'] = (int) $matches[1];
        }

        if (preg_match('/var map_default_y = (\d+);/', $content, $matches)) {
            $fieldTypeData['default_y'] = (int) $matches[1];
        }

        // Parse field type arrays
        $pattern = '/field_type\[(\d+)\] = new Array\(([^)]+)\);/';
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $row = (int) $match[1];
                $values = array_map('intval', explode(',', $match[2]));
                $fieldTypeData['field_types'][$row] = $values;
            }
        }

        return $fieldTypeData;
    }

    /**
     * Parse a specific layer array from the JavaScript content.
     */
    private function parseLayerArray(string $content, string $layerName, int $width, int $height): array
    {
        $layer = [];

        // Find all assignments for this layer
        $pattern = "/{$layerName}\[(\d+)\]\[(\d+)\] = '([^']+)';/";
        
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $row = (int) $match[1];
                $col = (int) $match[2];
                $tileValue = $match[3];
                
                if (!isset($layer[$row])) {
                    $layer[$row] = [];
                }
                
                $layer[$row][$col] = $tileValue;
            }
        }

        return $layer;
    }

    /**
     * Convert parsed JavaScript data to our standard format.
     */
    private function convertToStandardFormat(array $mapData, ?array $fieldTypeData = null): array
    {
        $this->tilesetMapping = [];

        $result = [
            'map' => [
                'name' => $mapData['name'] ?: 'Legacy Map',
                'width' => $mapData['width'],
                'height' => $mapData['height'],
                'tile_width' => 32,
                'tile_height' => 32,
                'external_creator' => $mapData['external_creator'],
            ],
            'layers' => [],
            'tilesets' => [],
        ];

        $layerMappings = [
            'field_bg' => ['type' => 'background', 'z' => 0],
            'field_layer1' => ['type' => 'floor', 'z' => 1],
            'field_layer2' => ['type' => 'floor', 'z' => 2],
            'field_layer4' => ['type' => 'sky', 'z' => 3],
            'field_layer5' => ['type' => 'sky', 'z' => 4],
        ];

        $tilesetUsage = $this->scanTilesetUsage($mapData, $layerMappings);
        $tilesetModels = $this->buildTilesetModels($tilesetUsage);
        $result['layers'] = $this->buildLayerData($mapData, $layerMappings, $tilesetModels);
        
        // Add field type layer if data exists
        if ($fieldTypeData && !empty($fieldTypeData['field_types'])) {
            $fieldTypeLayer = $this->buildFieldTypeLayer($fieldTypeData, $mapData['width'], $mapData['height']);
            if ($fieldTypeLayer) {
                $result['layers'][] = $fieldTypeLayer;
            }
        }
        
        $result['tilesets'] = $this->buildTilesetsArray($tilesetModels);

        return $result;
    }

    /**
     * Build field type layer from parsed data.
     */
    private function buildFieldTypeLayer(array $fieldTypeData, int $width, int $height): ?array
    {
        if (empty($fieldTypeData['field_types'])) {
            return null;
        }

        $layer = [
            'name' => 'Field Types',
            'type' => 'field_type',
            'x' => 0,
            'y' => 0,
            'z' => 10, // Place field types above other layers
            'width' => $width,
            'height' => $height,
            'visible' => true,
            'opacity' => 1.0,
            'data' => [],
        ];

        // Convert field type values according to the mapping
        // old value: 1 => field_type id 3 (walkable with monsters)
        // old value: 2 => field_type id 1 (walkable without monsters) 
        // old value: 3 => field_type id 2 (not walkable)
        $valueMapping = [
            1 => 3, // walkable with monsters
            2 => 1, // walkable without monsters
            3 => 2, // not walkable
        ];

        foreach ($fieldTypeData['field_types'] as $row => $rowData) {
            foreach ($rowData as $col => $oldValue) {
                if (isset($valueMapping[$oldValue])) {
                    $layer['data'][] = [
                        'x' => $col,
                        'y' => $row,
                        'fieldType' => $valueMapping[$oldValue],
                    ];
                }
            }
        }

        return $layer;
    }

    private function scanTilesetUsage(array $mapData, array $layerMappings): array
    {
        $tilesetUsage = [];
        foreach ($layerMappings as $jsLayerName => $config) {
            if (!isset($mapData['layers'][$jsLayerName])) {
                continue;
            }
            $jsLayer = $mapData['layers'][$jsLayerName];
            $width = $mapData['width'];
            $height = $mapData['height'];
            for ($row = 0; $row < $height; $row++) {
                for ($col = 0; $col < $width; $col++) {
                    if (isset($jsLayer[$row][$col])) {
                        $tileValue = $jsLayer[$row][$col];
                        if (preg_match('/^([^\/]+)\/(\d+)\.png$/', $tileValue, $matches)) {
                            $tilesetName = $matches[1];
                            $tileId = (int) $matches[2];
                            $tilesetUsage[$tilesetName][] = $tileId;
                        }
                    }
                }
            }
        }
        return $tilesetUsage;
    }

    private function buildTilesetModels(array $tilesetUsage): array
    {
        $tilesetModels = [];
        foreach ($tilesetUsage as $tilesetName => $tileIds) {
            $existingTileset = TileSet::where('name', $tilesetName)->first();
            if ($existingTileset) {
                $tilesetModels[$tilesetName] = $existingTileset;
            } else {
                $basename = $tilesetName . '.png';
                $imageFile = null;
                $searchDirs = [
                    isset($this->tilesetDirectory)
                        ? (str_starts_with($this->tilesetDirectory, '/')
                            ? rtrim($this->tilesetDirectory, '/') . '/' . $basename
                            : base_path(trim($this->tilesetDirectory, '/') . '/' . $basename))
                        : null,
                    base_path('tests/static/tilesets/' . $basename),
                ];
                foreach (array_filter($searchDirs) as $src) {
                    if (file_exists($src)) {
                        $imageFile = $src;
                        break;
                    }
                }
                if (!$imageFile) {
                    throw new \Exception("Tileset image file not found: {$tilesetName}");
                }
                $imgInfo = @getimagesize($imageFile);
                if (!$imgInfo) {
                    throw new \Exception("Could not read image size for tileset: {$tilesetName}");
                }
                $imageWidth = $imgInfo[0];
                $imageHeight = $imgInfo[1];
                if ($imageWidth <= 0 || $imageHeight <= 0) {
                    throw new \Exception("Invalid image dimensions for tileset: {$tilesetName}");
                }
                $tileWidth = 32;
                $tileHeight = 32;
                $tilesPerRow = (int) ($imageWidth / $tileWidth);
                if ($tilesPerRow <= 0) {
                    throw new \Exception("Invalid tilesPerRow for tileset: {$tilesetName}");
                }
                $maxTileId = max($tileIds);
                $tilesPerCol = (int) ceil($maxTileId / $tilesPerRow);
                $tilesetModels[$tilesetName] = [
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'name' => $tilesetName,
                    'image_width' => $imageWidth,
                    'image_height' => $imageHeight,
                    'tile_width' => $tileWidth,
                    'tile_height' => $tileHeight,
                    'tiles_per_row' => $tilesPerRow,
                    'image_path' => "tilesets/{$tilesetName}.png",
                ];
            }
        }
        return $tilesetModels;
    }

    private function buildLayerData(array $mapData, array $layerMappings, array $tilesetModels): array
    {
        $layers = [];
        foreach ($layerMappings as $jsLayerName => $config) {
            if (!isset($mapData['layers'][$jsLayerName])) {
                continue;
            }
            $jsLayer = $mapData['layers'][$jsLayerName];
            $width = $mapData['width'];
            $height = $mapData['height'];
            $layer = [
                'name' => ucfirst($config['type']) . ' Layer',
                'type' => $config['type'],
                'x' => 0,
                'y' => 0,
                'z' => $config['z'],
                'width' => $width,
                'height' => $height,
                'visible' => true,
                'opacity' => 1.0,
                'data' => [],
            ];
            for ($row = 0; $row < $height; $row++) {
                for ($col = 0; $col < $width; $col++) {
                    if (isset($jsLayer[$row][$col])) {
                        $tileValue = $jsLayer[$row][$col];
                        if (preg_match('/^([^\/]+)\/(\d+)\.png$/', $tileValue, $matches)) {
                            $tilesetName = $matches[1];
                            $tileId = (int) $matches[2];
                            $tileset = $tilesetModels[$tilesetName] ?? null;
                            if (!$tileset) {
                                throw new \Exception("Tileset not found in models: {$tilesetName}");
                            }
                            $tilesPerRow = $tileset['tiles_per_row'] ?? 0;
                            if ($tilesPerRow <= 0) {
                                throw new \Exception("Invalid tilesPerRow for tileset: {$tilesetName}");
                            }
                            $adjustedTileId = $tileId - 1;
                            $tileX = $adjustedTileId % $tilesPerRow;
                            $tileY = (int) ($adjustedTileId / $tilesPerRow);

                            $layer['data'][] = [
                                'x' => $col,
                                'y' => $row,
                                'brush' => [
                                    'tileset' => $tileset['uuid'],
                                    'tileX' => $tileX,
                                    'tileY' => $tileY,
                                ]
                            ];
                        }
                    }
                }
            }
            if (!empty($layer['data'])) {
                $layers[] = $layer;
            }
        }
        return $layers;
    }

    private function buildTilesetsArray(array $tilesetModels): array
    {
        return array_values(array_map(function ($ts) {
            if ($ts instanceof \App\Models\TileSet) {
                return [
                    'uuid' => $ts->uuid,
                    'name' => $ts->name,
                    'image_width' => $ts->image_width,
                    'image_height' => $ts->image_height,
                    'tile_width' => $ts->tile_width,
                    'tile_height' => $ts->tile_height,
                    'image_path' => $ts->image_path,
                    'margin' => $ts->margin,
                    'spacing' => $ts->spacing,
                    '_existing' => true
                ];
            }
            return $ts + ['_existing' => false];
        }, $tilesetModels));
    }

    /**
     * Parse a tile value like 'castle_exterior_mc/761.png' into tileset and tile ID.
     */
    private function parseTileValue(string $tileValue): ?array
    {
        // Extract tileset name and tile ID from path like 'castle_exterior_mc/761.png'
        if (!preg_match('/^([^\/]+)\/(\d+)\.png$/', $tileValue, $matches)) {
            return null;
        }

        $tilesetName = $matches[1];
        $tileId = (int) $matches[2];

        // Generate or get UUID for this tileset
        if (!isset($this->tilesetMapping[$tilesetName])) {
            $formattedName = $this->formatTilesetName($tilesetName);
            
            // First, try to find existing tileset by name
            $existingTileset = $this->findExistingTileset($tilesetName, $formattedName);
            
            if ($existingTileset) {
                // Use existing tileset
                $this->tilesetMapping[$tilesetName] = [
                    'uuid' => $existingTileset->uuid,
                    'name' => $existingTileset->name,
                    'tiles' => [],
                    'existing' => true
                ];
            } else {
                // Create new tileset mapping
                $this->tilesetMapping[$tilesetName] = [
                    'uuid' => $this->generateTilesetUuid($tilesetName),
                    'name' => $formattedName,
                    'tiles' => [],
                    'existing' => false
                ];
            }
        }

        // Track this tile ID
        $this->tilesetMapping[$tilesetName]['tiles'][] = $tileId;

        // Convert tile_id to tileX/tileY coordinates
        $tileCoords = $this->convertTileIdToCoordinates($tileId, $tilesetName);

        return [
            'tileset_uuid' => $this->tilesetMapping[$tilesetName]['uuid'],
            'tileX' => $tileCoords['tileX'],
            'tileY' => $tileCoords['tileY'],
        ];
    }

    /**
     * Convert linear tile_id to 2D tileX/tileY coordinates.
     */
    private function convertTileIdToCoordinates(int $tileId, string $tilesetName): array
    {
        $tilesPerRow = null;
        // If tileset exists in mapping and was found in database, use the model accessor
        if (isset($this->tilesetMapping[$tilesetName]['existing']) && $this->tilesetMapping[$tilesetName]['existing']) {
            $existingTileset = TileSet::where('uuid', $this->tilesetMapping[$tilesetName]['uuid'])->first();
            if ($existingTileset && $existingTileset->tiles_per_row > 0) {
                $tilesPerRow = $existingTileset->tiles_per_row;
            } else {
                throw new \Exception("Cannot determine tilesPerRow for existing tileset: {$tilesetName}");
            }
        } else if (
            isset($this->tilesetMapping[$tilesetName]['image_width']) &&
            isset($this->tilesetMapping[$tilesetName]['tile_width']) &&
            $this->tilesetMapping[$tilesetName]['tile_width'] > 0
        ) {
            $tilesPerRow = (int) ($this->tilesetMapping[$tilesetName]['image_width'] / $this->tilesetMapping[$tilesetName]['tile_width']);
            if ($tilesPerRow <= 0) {
                throw new \Exception("Invalid tilesPerRow for tileset: {$tilesetName}");
            }
        } else {
            dd($this->tilesetMapping[$tilesetName]);
            throw new \Exception("Cannot determine tilesPerRow for tileset: {$tilesetName}");
        }
        $adjustedTileId = $tileId - 1;
        return [
            'tileX' => $tilesPerRow > 0 ? $adjustedTileId % $tilesPerRow : 0,
            'tileY' => $tilesPerRow > 0 ? intval($adjustedTileId / $tilesPerRow) : 0,
        ];
    }

    /**
     * Find an existing tileset by name variations.
     */
    private function findExistingTileset(string $originalName, string $formattedName): ?TileSet
    {
        // Try multiple name variations to find existing tileset
        $searchNames = [
            $formattedName,        // "Castle Exterior Mc"
            $originalName,         // "castle_exterior_mc"
            ucwords($originalName, '_'), // "Castle_Exterior_Mc"
            str_replace('_', ' ', $originalName), // "castle exterior mc"
            ucfirst(str_replace('_', ' ', $originalName)), // "Castle exterior mc"
        ];

        foreach ($searchNames as $searchName) {
            $tileset = TileSet::where('name', 'LIKE', $searchName)->first();
            if ($tileset) {
                return $tileset;
            }
        }

        return null;
    }

    /**
     * Generate a deterministic UUID for a tileset name.
     */
    private function generateTilesetUuid(string $tilesetName): string
    {
        // Create a deterministic UUID based on the tileset name
        // This ensures consistent UUIDs across imports of the same legacy map
        $hash = md5('legacy_tileset_' . $tilesetName);
        
        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12)
        );
    }

    /**
     * Format tileset name for display.
     */
    private function formatTilesetName(string $tilesetName): string
    {
        // Keep the original name format for legacy tilesets
        return $tilesetName;
    }
} 