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
use App\Services\Importers\LaxLegacyImporter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\ValueObjects\Tile;
use App\ValueObjects\Brush;
use Illuminate\Support\Str;

class MapImportService
{
    private array $importers = [];

    public function __construct()
    {
        $this->registerImporter('json', new JsonMapImporter());
        $this->registerImporter('tmx', new TmxMapImporter());
        $this->registerImporter('js', new LaxLegacyImporter());
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
        
        // Configure importer with options
        if ($importer instanceof LaxLegacyImporter && isset($options['tileset_directory'])) {
            $importer->setTilesetDirectory($options['tileset_directory']);
        }
        
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
        
        // Configure importer with options
        if ($importer instanceof LaxLegacyImporter && isset($options['tileset_directory'])) {
            $importer->setTilesetDirectory($options['tileset_directory']);
        }
        
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
     * Import or verify tilesets.
     */
    private function importTilesets(array $tilesets, array $options = []): array
    {
        $missingTilesets = [];
        $createdTilesets = [];
        $existingTilesets = [];
        $uuidMap = [];

        foreach ($tilesets as &$tilesetData) {
            $originalTmxUuid = $tilesetData['uuid'] ?? null;
            $existingTileset = null;
            // 1. Try to find by UUID
            if (isset($tilesetData['uuid'])) {
                $existingTileset = TileSet::where('uuid', $tilesetData['uuid'])->first();
            }
            // 2. Try to find by name (case-insensitive, in PHP)
            if (!$existingTileset && isset($tilesetData['name'])) {
                $existingTileset = TileSet::all()->first(function ($ts) use ($tilesetData) {
                    return mb_strtolower($ts->name) === mb_strtolower($tilesetData['name']);
                });
            }
            // 3. Try to find by image_path (basename, case-insensitive)
            if (!$existingTileset && isset($tilesetData['image_path'])) {
                $basename = basename($tilesetData['image_path']);
                $existingTileset = TileSet::whereRaw('LOWER(image_path) LIKE ?', ['%' . strtolower($basename)])->first();
            }
            // If found, update the import data UUID to match and track mapping
            if ($existingTileset) {
                if ($originalTmxUuid && $originalTmxUuid !== $existingTileset->uuid) {
                    $uuidMap[$originalTmxUuid] = $existingTileset->uuid;
                }
                $tilesetData['uuid'] = $existingTileset->uuid;
                $existingTilesets[] = $tilesetData;
                continue;
            }
            // Not found, create if allowed
            if ($options['auto_create_tilesets'] ?? false) {
                $created = $this->createMissingTileset($tilesetData, $options);
                if ($originalTmxUuid && $originalTmxUuid !== $created->uuid) {
                    $uuidMap[$originalTmxUuid] = $created->uuid;
                }
                $createdTilesets[] = $created;
            } else {
                $missingTilesets[] = $tilesetData;
            }
        }
        unset($tilesetData);

        // Only report missing tilesets if auto-creation is disabled
        if (($options['auto_create_tilesets'] ?? false)) {
            $missingTilesets = [];
        }

        return [
            'missing' => $missingTilesets,
            'created' => $createdTilesets,
            'existing' => $existingTilesets,
            'uuid_map' => $uuidMap,
        ];
    }

    /**
     * Create a missing tileset from import data.
     */
    private function createMissingTileset(array $tilesetData, array $options = []): TileSet
    {
        $tileset = new TileSet();
        $tileset->uuid = $tilesetData['uuid'] ?? (string) \Illuminate\Support\Str::uuid();
        $tileset->name = $tilesetData['name'] ?? 'Imported Tileset';
        $tileset->image_width = (int) ($tilesetData['image_width'] ?? 0);
        $tileset->image_height = (int) ($tilesetData['image_height'] ?? 0);
        $tileset->tile_width = (int) ($tilesetData['tile_width'] ?? 32);
        $tileset->tile_height = (int) ($tilesetData['tile_height'] ?? 32);

        // Always copy the image into storage/app/public/tilesets/
        $originalImagePath = $tilesetData['image_path'] ?? null;
        if ($originalImagePath) {
            $basename = basename($originalImagePath);
            $storagePath = 'tilesets/' . $basename;
            $publicDisk = Storage::disk('public');

            // Build possible source paths, including custom directory if provided
            $sourcePaths = [
                // Custom directory as source (handle absolute and relative)
                isset($options['tileset_directory'])
                    ? (str_starts_with($options['tileset_directory'], '/')
                        ? rtrim($options['tileset_directory'], '/') . '/' . $basename
                        : base_path(trim($options['tileset_directory'], '/') . '/' . $basename))
                    : null,
                // Path as given in image_path
                base_path($originalImagePath),
                // Test static tilesets
                base_path('tests/static/tilesets/' . $basename),
                // Storage path
                Storage::path($originalImagePath),
            ];
            $found = false;
            foreach (array_filter($sourcePaths) as $src) {
                if (file_exists($src)) {
                    $publicDisk->put($storagePath, file_get_contents($src));
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new \RuntimeException("Tileset image file not found: {$originalImagePath}");
            }
            $tileset->image_path = $storagePath;
        } else {
            $tileset->image_path = null;
        }
        
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
        
        // Handle data based on layer type
        if ($layer->type === LayerType::FieldType) {
            // Handle field type data
            $layer->data = array_map(function ($fieldTypeTile) {
                return [
                    'x' => (int) ($fieldTypeTile['x'] ?? 0),
                    'y' => (int) ($fieldTypeTile['y'] ?? 0),
                    'fieldType' => (int) ($fieldTypeTile['fieldType'] ?? 1),
                ];
            }, $layerData['data'] ?? []);
        } else {
            // Handle regular tile data
            $layer->data = array_map(function ($tile) {
                return new Tile(
                    $tile['x'],
                    $tile['y'],
                    new Brush(
                        $tile['brush']['tileset'] ?? '',
                        $tile['brush']['tileX'] ?? 0,
                        $tile['brush']['tileY'] ?? 0,
                    )
                );
            }, $layerData['data'] ?? []);
        }

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
            'object' => LayerType::Object,
            'field_type', 'fieldtype' => LayerType::FieldType,
            default => LayerType::Floor, // Default fallback
        };
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
        $uuidMap = $tilesetResults['uuid_map'] ?? [];
        // Rewrite all tile brush tileset UUIDs in layers (only for non-field-type layers)
        foreach ($mapData['layers'] as &$layer) {
            $layerType = $layer['type'] ?? 'floor';
            if ($layerType !== 'field_type') {
                foreach ($layer['data'] as &$tile) {
                    if (isset($tile['brush']['tileset'])) {
                        $oldUuid = $tile['brush']['tileset'];
                        if (isset($uuidMap[$oldUuid])) {
                            $tile['brush']['tileset'] = $uuidMap[$oldUuid];
                        }
                    }
                }
                unset($tile);
            }
        }
        unset($layer);

        // Create the map
        $map = new TileMap();
        $map->name = $mapInfo['name'];
        $map->width = (int) $mapInfo['width'];
        $map->height = (int) $mapInfo['height'];
        $map->tile_width = (int) $mapInfo['tile_width'];
        $map->tile_height = (int) $mapInfo['tile_height'];
        
        if (isset($mapInfo['external_creator'])) {
            $map->external_creator = $mapInfo['external_creator'];
        }
        
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
} 