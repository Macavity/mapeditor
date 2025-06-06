<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TileMap;
use App\Models\Layer;
use App\Models\TileSet;
use App\Models\User;
use App\Enums\LayerType;
use App\Services\Importers\ImporterInterface;
use App\Services\Importers\JsonMapImporter;
use App\Services\Importers\TmxMapImporter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class MapImportService
{
    private array $importers = [];

    public function __construct()
    {
        $this->registerImporter('json', new JsonMapImporter());
        $this->registerImporter('tmx', new TmxMapImporter());
    }

    /**
     * Register a new format importer.
     */
    public function registerImporter(string $format, ImporterInterface $importer): void
    {
        $this->importers[$format] = $importer;
    }

    /**
     * Get supported import formats.
     */
    public function getSupportedFormats(): array
    {
        return array_keys($this->importers);
    }

    /**
     * Validate import format.
     */
    public function isValidFormat(string $format): bool
    {
        return isset($this->importers[$format]);
    }

    /**
     * Detect format from file extension or content.
     */
    public function detectFormat(string $filePath): ?string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if (isset($this->importers[$extension])) {
            return $extension;
        }

        // Try to detect from content if extension detection fails
        foreach ($this->importers as $format => $importer) {
            if ($importer->canHandle($filePath)) {
                return $format;
            }
        }

        return null;
    }

    /**
     * Import a map from file.
     */
    public function importFromFile(string $filePath, string $format, ?User $creator = null, array $options = []): array
    {
        if (!$this->isValidFormat($format)) {
            throw new \InvalidArgumentException("Unsupported import format: {$format}");
        }

        if (!Storage::exists($filePath) && !file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $importer = $this->importers[$format];
        
        // Parse the file data
        $mapData = $importer->parse($filePath);
        
        // Validate the parsed data
        $this->validateMapData($mapData);
        
        // Import the map with transaction safety
        return DB::transaction(function () use ($mapData, $creator, $options) {
            return $this->createMapFromData($mapData, $creator, $options);
        });
    }

    /**
     * Import a map from raw data string.
     */
    public function importFromString(string $data, string $format, ?User $creator = null, array $options = []): array
    {
        if (!$this->isValidFormat($format)) {
            throw new \InvalidArgumentException("Unsupported import format: {$format}");
        }

        $importer = $this->importers[$format];
        
        // Parse the raw data
        $mapData = $importer->parseString($data);
        
        // Validate the parsed data
        $this->validateMapData($mapData);
        
        // Import the map with transaction safety
        return DB::transaction(function () use ($mapData, $creator, $options) {
            return $this->createMapFromData($mapData, $creator, $options);
        });
    }

    /**
     * Validate parsed map data structure.
     */
    private function validateMapData(array $mapData): void
    {
        $requiredFields = ['map', 'layers'];
        
        foreach ($requiredFields as $field) {
            if (!isset($mapData[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        // Validate map structure
        $requiredMapFields = ['name', 'width', 'height', 'tile_width', 'tile_height'];
        foreach ($requiredMapFields as $field) {
            if (!isset($mapData['map'][$field])) {
                throw new \InvalidArgumentException("Missing required map field: {$field}");
            }
        }

        // Validate layers are array
        if (!is_array($mapData['layers'])) {
            throw new \InvalidArgumentException("Layers must be an array");
        }

        // Validate tilesets if present
        if (isset($mapData['tilesets']) && !is_array($mapData['tilesets'])) {
            throw new \InvalidArgumentException("Tilesets must be an array");
        }
    }

    /**
     * Create a TileMap and related entities from parsed data.
     */
    private function createMapFromData(array $mapData, ?User $creator = null, array $options = []): array
    {
        $mapInfo = $mapData['map'];
        
        // Handle UUID conflicts if specified in options
        if (isset($options['preserve_uuid']) && $options['preserve_uuid'] && isset($mapInfo['uuid'])) {
            if (TileMap::where('uuid', $mapInfo['uuid'])->exists()) {
                if (!($options['overwrite'] ?? false)) {
                    throw new \RuntimeException("Map with UUID {$mapInfo['uuid']} already exists. Use --overwrite to replace it.");
                }
                // Delete existing map if overwrite is enabled
                TileMap::where('uuid', $mapInfo['uuid'])->delete();
            }
        }

        // Create or import tilesets first
        $tilesetResults = $this->importTilesets($mapData['tilesets'] ?? [], $options);

        // Create the map
        $map = new TileMap();
        $map->name = $mapInfo['name'];
        $map->width = (int) $mapInfo['width'];
        $map->height = (int) $mapInfo['height'];
        $map->tile_width = (int) $mapInfo['tile_width'];
        $map->tile_height = (int) $mapInfo['tile_height'];
        
        if ($creator) {
            $map->creator_id = $creator->id;
        }

        // Preserve UUID if requested and valid
        if (isset($options['preserve_uuid']) && $options['preserve_uuid'] && isset($mapInfo['uuid'])) {
            $map->uuid = $mapInfo['uuid'];
        }

        $map->save();

        // Create layers
        foreach ($mapData['layers'] as $layerData) {
            $this->createLayer($map, $layerData, $options);
        }

        return [
            'map' => $map->fresh(['creator', 'layers']),
            'tilesets' => $tilesetResults,
        ];
    }

    /**
     * Import or verify tilesets.
     */
    private function importTilesets(array $tilesets, array $options = []): array
    {
        $missingTilesets = [];
        $createdTilesets = [];

        foreach ($tilesets as $tilesetData) {
            if (!isset($tilesetData['uuid'])) {
                continue;
            }

            // Check if tileset already exists
            $existingTileset = TileSet::where('uuid', $tilesetData['uuid'])->first();
            
            if (!$existingTileset) {
                $missingTilesets[] = $tilesetData;
                
                // Only auto-create if explicitly allowed
                if ($options['auto_create_tilesets'] ?? false) {
                    $createdTilesets[] = $this->createMissingTileset($tilesetData);
                }
            }
        }

        // If we have missing tilesets and auto-creation is disabled, throw an error
        if (!empty($missingTilesets) && !($options['auto_create_tilesets'] ?? false)) {
            $missingNames = array_map(fn($ts) => $ts['name'] ?? $ts['uuid'], $missingTilesets);
            throw new \RuntimeException(
                "Missing tilesets: " . implode(', ', $missingNames) . 
                ". Use --auto-create-tilesets to create them automatically, but note that image files may be missing."
            );
        }

        return [
            'missing' => $missingTilesets,
            'created' => $createdTilesets,
        ];
    }

    /**
     * Create a missing tileset from import data.
     */
    private function createMissingTileset(array $tilesetData): TileSet
    {
        $tileset = new TileSet();
        $tileset->uuid = $tilesetData['uuid'];
        $tileset->name = $tilesetData['name'] ?? 'Imported Tileset';
        $tileset->image_width = (int) ($tilesetData['image_width'] ?? 0);
        $tileset->image_height = (int) ($tilesetData['image_height'] ?? 0);
        $tileset->tile_width = (int) ($tilesetData['tile_width'] ?? 32);
        $tileset->tile_height = (int) ($tilesetData['tile_height'] ?? 32);
        $tileset->image_url = $tilesetData['image_url'] ?? null;
        $tileset->image_path = $tilesetData['image_path'] ?? null;
        
        // Calculate tile count if we have complete data
        if ($tileset->image_width > 0 && $tileset->image_height > 0 && 
            $tileset->tile_width > 0 && $tileset->tile_height > 0) {
            $tilesPerRow = intval($tileset->image_width / $tileset->tile_width);
            $tilesPerCol = intval($tileset->image_height / $tileset->tile_height);
            $tileset->tile_count = $tilesPerRow * $tilesPerCol;
        } else {
            $tileset->tile_count = 0;
        }
        
        $tileset->first_gid = 1; // Default value
        $tileset->margin = (int) ($tilesetData['margin'] ?? 0);
        $tileset->spacing = (int) ($tilesetData['spacing'] ?? 0);
        $tileset->save();

        return $tileset;
    }

    /**
     * Create a layer from layer data.
     */
    private function createLayer(TileMap $map, array $layerData, array $options): void
    {
        $layer = new Layer();
        $layer->tile_map_id = $map->id;
        $layer->name = $layerData['name'] ?? 'Imported Layer';
        
        // Handle layer type with enum validation
        $layerType = $layerData['type'] ?? 'floor';
        $layer->type = $this->validateLayerType($layerType);
        
        $layer->x = (int) ($layerData['x'] ?? 0);
        $layer->y = (int) ($layerData['y'] ?? 0);
        $layer->z = (int) ($layerData['z'] ?? 0);
        $layer->width = (int) ($layerData['width'] ?? $map->width);
        $layer->height = (int) ($layerData['height'] ?? $map->height);
        $layer->visible = $layerData['visible'] ?? true;
        $layer->opacity = (float) ($layerData['opacity'] ?? 1.0);
        $layer->data = $layerData['data'] ?? [];

        // Preserve UUID if requested and valid
        if (isset($options['preserve_uuid']) && $options['preserve_uuid'] && isset($layerData['uuid'])) {
            $layer->uuid = $layerData['uuid'];
        }

        $layer->save();
    }

    /**
     * Validate and normalize layer type to enum value.
     */
    private function validateLayerType(string $type): LayerType
    {
        // Try to match the type to a valid enum case
        $type = strtolower($type);
        
        return match ($type) {
            'background' => LayerType::Background,
            'floor' => LayerType::Floor,
            'sky' => LayerType::Sky,
            'field_type', 'fieldtype' => LayerType::FieldType,
            default => LayerType::Floor, // Default fallback
        };
    }
} 