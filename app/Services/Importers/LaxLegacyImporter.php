<?php

declare(strict_types=1);

namespace App\Services\Importers;

use App\Models\TileSet;
use Illuminate\Support\Facades\Storage;

class LaxLegacyImporter implements ImporterInterface
{
    private array $tilesetMapping = [];

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

        return $this->parseString($content);
    }

    /**
     * Parse raw JavaScript data string and return structured data.
     */
    public function parseString(string $data): array
    {
        // Parse the JavaScript variables
        $mapData = $this->parseJavaScriptVariables($data);
        
        // Convert to our standard format
        return $this->convertToStandardFormat($mapData);
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
        return 'Imports legacy JavaScript map files (field_bg, field_layer1, etc.)';
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
    private function convertToStandardFormat(array $mapData): array
    {
        // Reset tileset mapping for this import
        $this->tilesetMapping = [];

        $result = [
            'map' => [
                'name' => $mapData['name'] ?: 'Legacy Map',
                'width' => $mapData['width'],
                'height' => $mapData['height'],
                'tile_width' => 32, // Default tile size
                'tile_height' => 32,
                'external_creator' => $mapData['external_creator'],
            ],
            'layers' => [],
            'tilesets' => [],
        ];

        // Process layers in correct order (background first, then floor, then sky)
        $layerMappings = [
            'field_bg' => ['type' => 'background', 'z' => 0],
            'field_layer1' => ['type' => 'floor', 'z' => 1],
            'field_layer2' => ['type' => 'floor', 'z' => 2],
            'field_layer4' => ['type' => 'sky', 'z' => 3],
            'field_layer5' => ['type' => 'sky', 'z' => 4],
        ];

        foreach ($layerMappings as $jsLayerName => $config) {
            if (!isset($mapData['layers'][$jsLayerName])) {
                continue;
            }

            $layer = $this->convertLayer(
                $mapData['layers'][$jsLayerName],
                $config['type'],
                $config['z'],
                $mapData['width'],
                $mapData['height'],
                $jsLayerName === 'field_bg' ? $mapData['main_bg'] : null
            );

            if (!empty($layer['data'])) {
                $result['layers'][] = $layer;
            }
        }

        // Generate tilesets from the collected mapping
        $result['tilesets'] = $this->generateTilesets();

        return $result;
    }

    /**
     * Convert a single layer from JavaScript format to our format.
     */
    private function convertLayer(array $jsLayer, string $type, int $z, int $width, int $height, ?string $defaultTile = null): array
    {
        $layer = [
            'name' => ucfirst($type) . ' Layer',
            'type' => $type,
            'x' => 0,
            'y' => 0,
            'z' => $z,
            'width' => $width,
            'height' => $height,
            'visible' => true,
            'opacity' => 1.0,
            'data' => [],
        ];

        // Process each tile position
        for ($row = 0; $row < $height; $row++) {
            for ($col = 0; $col < $width; $col++) {
                $tileValue = null;

                // Check if this position has a tile
                if (isset($jsLayer[$row][$col])) {
                    $tileValue = $jsLayer[$row][$col];
                } elseif ($defaultTile && $type === 'background') {
                    // Fill background with main_bg if no specific tile
                    $tileValue = $defaultTile;
                }

                if ($tileValue) {
                    $tileInfo = $this->parseTileValue($tileValue);
                    if ($tileInfo) {
                        $layer['data'][] = [
                            'x' => $col,
                            'y' => $row,
                            'brush' => [
                                'tileset' => $tileInfo['tileset_uuid'],
                                'tileX' => $tileInfo['tileX'],
                                'tileY' => $tileInfo['tileY'],
                            ]
                        ];
                    }
                }
            }
        }

        return $layer;
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
        $tilesPerRow = 16; // Default assumption for legacy tilesets
        
        // If tileset exists in mapping and was found in database, try to get actual tiles per row
        if (isset($this->tilesetMapping[$tilesetName]['existing']) && 
            $this->tilesetMapping[$tilesetName]['existing']) {
            
            $existingTileset = TileSet::where('uuid', $this->tilesetMapping[$tilesetName]['uuid'])->first();
            if ($existingTileset && $existingTileset->tile_width > 0) {
                $tilesPerRow = intval($existingTileset->image_width / $existingTileset->tile_width);
            }
        }
        
        // Adjust for 1-based tile IDs (subtract 1 to make it 0-based)
        $adjustedTileId = $tileId - 1;
        
        return [
            'tileX' => $adjustedTileId % $tilesPerRow,
            'tileY' => intval($adjustedTileId / $tilesPerRow),
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
        // Convert snake_case to Title Case
        return ucwords(str_replace('_', ' ', $tilesetName));
    }

    /**
     * Generate tileset definitions from the collected mapping.
     */
    private function generateTilesets(): array
    {
        $tilesets = [];

        foreach ($this->tilesetMapping as $tilesetName => $info) {
            if (isset($info['existing']) && $info['existing']) {
                // For existing tilesets, just include UUID and name for reference
                $tilesets[] = [
                    'uuid' => $info['uuid'],
                    'name' => $info['name'],
                    '_existing' => true
                ];
            } else {
                // For new tilesets, include full definition
                $maxTileId = !empty($info['tiles']) ? max($info['tiles']) : 1;
                
                // Calculate reasonable tileset dimensions
                // Assume 16 tiles per row as a default for legacy tilesets
                $tilesPerRow = 16;
                $tilesPerCol = (int) ceil($maxTileId / $tilesPerRow);
                
                $tilesets[] = [
                    'uuid' => $info['uuid'],
                    'name' => $info['name'],
                    'image_width' => $tilesPerRow * 32, // Assume 32px tiles
                    'image_height' => $tilesPerCol * 32,
                    'tile_width' => 32,
                    'tile_height' => 32,
                    'image_url' => "legacy/{$tilesetName}.png", // Suggested path
                    'image_path' => null,
                    'margin' => 0,
                    'spacing' => 0,
                    '_existing' => false
                ];
            }
        }

        return $tilesets;
    }
} 