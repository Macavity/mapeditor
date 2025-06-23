<?php

declare(strict_types=1);

namespace App\Services\Importers;

use App\Models\TileSet;
use App\Services\TileSetService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class LaxLegacyImporter implements ImporterInterface
{
    public function __construct(private TileSetService $tilesetService)
    {
    }

    /**
     * Set the tileset directory for this import.
     */
    public function setTilesetDirectory(string $directory): self
    {
        $this->tilesetService->setTilesetDirectory($directory);
        return $this;
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

        // Handle encoding issues gracefully
        $content = $this->fixEncoding($content);

        return $this->parseString($content, $filePath);
    }

    /**
     * Parse raw JavaScript data string and return structured data.
     */
    public function parseString(string $data, string $originalFilePath = ''): array
    {
        // Handle encoding issues gracefully
        $data = $this->fixEncoding($data);

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
            Log::error("LaxLegacyImporter::canHandle - Exception", [
                'filePath' => $filePath,
                'exception' => $e->getMessage()
            ]);
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

        $tilesetUsage = $this->tilesetService->scanTilesetUsage($mapData, $layerMappings);
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

    private function buildTilesetModels(array $tilesetUsage): array
    {
        $tilesetModels = [];
        foreach ($tilesetUsage as $tilesetName => $tileIds) {
            $tilesetModels[$tilesetName] = $this->tilesetService->buildTilesetModelData($tilesetName, $tileIds, false);
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
                            
                            $coordinates = $this->tilesetService->convertTileIdToCoordinates($tileId, $tilesPerRow);

                            $layer['data'][] = [
                                'x' => $col,
                                'y' => $row,
                                'brush' => [
                                    'tileset' => $tileset['uuid'],
                                    'tileX' => $coordinates['tileX'],
                                    'tileY' => $coordinates['tileY'],
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
     * Parse a map file for the import wizard, returning basic information and tileset usage.
     * This method is optimized for the wizard and doesn't build complete tileset models.
     */
    public function parseForWizard(string $filePath): array
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

        // Handle encoding issues gracefully
        $content = $this->fixEncoding($content);

        return $this->parseStringForWizard($content, $filePath);
    }

    /**
     * Fix encoding issues in the content.
     */
    private function fixEncoding(string $content): string
    {
        // Check if content is valid UTF-8
        if (mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }

        // Try to detect the encoding
        $detectedEncoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'ISO-8859-15', 'Windows-1252'], true);
        
        if ($detectedEncoding && $detectedEncoding !== 'UTF-8') {
            // Convert to UTF-8
            $converted = mb_convert_encoding($content, 'UTF-8', $detectedEncoding);
            
            // Verify the conversion worked
            if (mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }

        // If all else fails, try to fix common encoding issues
        $fixed = iconv('UTF-8', 'UTF-8//IGNORE', $content);
        if ($fixed !== false) {
            return $fixed;
        }

        // Last resort: remove invalid characters
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);
    }

    /**
     * Parse raw JavaScript data string for the wizard, returning basic information and tileset usage.
     */
    public function parseStringForWizard(string $data, string $originalFilePath = ''): array
    {
        // Handle encoding issues gracefully
        $data = $this->fixEncoding($data);

        // Parse the JavaScript variables
        $mapData = $this->parseJavaScriptVariables($data);
        
        // Try to parse field type data if available
        $fieldTypeData = $this->parseFieldTypeFile($originalFilePath);
        
        // Scan for tileset usage without building models
        $tilesetUsage = $this->tilesetService->scanTilesetUsage($mapData, [
            'field_bg' => ['type' => 'background', 'z' => 0],
            'field_layer1' => ['type' => 'tile', 'z' => 1],
            'field_layer2' => ['type' => 'tile', 'z' => 2],
            'field_layer4' => ['type' => 'tile', 'z' => 4],
            'field_layer5' => ['type' => 'tile', 'z' => 5],
        ]);

        // Build basic tileset information with suggestions
        $tilesets = $this->tilesetService->buildTilesetSuggestions($tilesetUsage);

        return [
            'map_info' => [
                'name' => $mapData['name'],
                'width' => $mapData['width'],
                'height' => $mapData['height'],
                'external_creator' => $mapData['external_creator'],
                'has_field_types' => !empty($fieldTypeData),
            ],
            'tilesets' => $tilesets,
            'field_type_file' => $this->getFieldTypeFilePath($originalFilePath),
        ];
    }

    /**
     * Get the field type file path if it exists.
     */
    private function getFieldTypeFilePath(string $originalFilePath): ?string
    {
        if (empty($originalFilePath)) {
            return null;
        }

        $fieldTypePath = preg_replace('/\.js$/', '_ft.js', $originalFilePath);
        
        if (Storage::exists($fieldTypePath)) {
            return $fieldTypePath;
        }
        
        return null;
    }
} 