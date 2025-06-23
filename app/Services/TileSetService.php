<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TileSet;
use App\Repositories\TileSetRepository;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TileSetService
{
    private TileSetRepository $tilesetRepository;
    private string $tilesetDirectory;

    public function __construct(TileSetRepository $tilesetRepository, string $tilesetDirectory = 'tilesets')
    {
        $this->tilesetRepository = $tilesetRepository;
        $this->tilesetDirectory = $tilesetDirectory;
    }

    /**
     * Set the tileset directory for this service.
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
     * Find an existing tileset by name variations.
     */
    public function findExistingTileset(string $originalName, string $formattedName): ?TileSet
    {
        return $this->tilesetRepository->findByNameVariations($originalName, $formattedName);
    }

    /**
     * Check if a tileset image file exists.
     */
    public function checkTilesetImageExists(string $tilesetName, ?string $imageSource = null): bool
    {
        // Try the image source first if available
        if ($imageSource) {
            $searchDirs = [
                base_path('tilesets/' . basename($imageSource)),
                base_path('tests/static/tilesets/' . basename($imageSource)),
            ];
            
            foreach ($searchDirs as $src) {
                if (file_exists($src)) {
                    return true;
                }
            }
        }
        
        // Fallback to tileset name
        $basename = $tilesetName . '.png';
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
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get tileset image file path if it exists.
     */
    public function getTilesetImagePath(string $tilesetName): ?string
    {
        $basename = $tilesetName . '.png';
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
                return $src;
            }
        }
        
        return null;
    }

    /**
     * Parse tileset information from an image file.
     */
    public function parseTilesetFromImage(string $imagePath, int $tileWidth = 32, int $tileHeight = 32): array
    {
        $imgInfo = @getimagesize($imagePath);
        if (!$imgInfo) {
            throw new \Exception("Could not read image size for tileset: {$imagePath}");
        }

        $imageWidth = $imgInfo[0];
        $imageHeight = $imgInfo[1];

        if ($imageWidth <= 0 || $imageHeight <= 0) {
            throw new \Exception("Invalid image dimensions for tileset: {$imagePath}");
        }

        $tilesPerRow = (int) ($imageWidth / $tileWidth);
        if ($tilesPerRow <= 0) {
            throw new \Exception("Invalid tilesPerRow for tileset: {$imagePath}");
        }

        $tilesPerCol = (int) ($imageHeight / $tileHeight);
        $tileCount = $tilesPerRow * $tilesPerCol;

        return [
            'image_width' => $imageWidth,
            'image_height' => $imageHeight,
            'tile_width' => $tileWidth,
            'tile_height' => $tileHeight,
            'tiles_per_row' => $tilesPerRow,
            'tiles_per_col' => $tilesPerCol,
            'tile_count' => $tileCount,
        ];
    }

    /**
     * Build tileset model data from usage information.
     */
    public function buildTilesetModelData(
        string $tilesetName,
        array $tileIds = [],
        bool $skipValidation = false
    ): array {
        $formattedName = $this->formatTilesetName($tilesetName);
        $existingTileset = $this->findExistingTileset($tilesetName, $formattedName);
        
        if ($existingTileset) {
            return [
                'uuid' => $existingTileset->uuid,
                'name' => $existingTileset->name,
                'image_width' => $existingTileset->image_width,
                'image_height' => $existingTileset->image_height,
                'tile_width' => $existingTileset->tile_width,
                'tile_height' => $existingTileset->tile_height,
                'tiles_per_row' => $existingTileset->tiles_per_row,
                'image_path' => $existingTileset->image_path,
                '_existing' => true,
            ];
        }

        $imageFile = $this->getTilesetImagePath($tilesetName);
        
        if (!$imageFile) {
            if ($skipValidation) {
                // For wizard parsing, create minimal tileset data without image validation
                $maxTileId = !empty($tileIds) ? max($tileIds) : 0;
                return [
                    'uuid' => $this->generateTilesetUuid($tilesetName),
                    'name' => $tilesetName,
                    'image_width' => 0, // Will be set when image is uploaded
                    'image_height' => 0, // Will be set when image is uploaded
                    'tile_width' => 32, // Default assumption
                    'tile_height' => 32, // Default assumption
                    'tiles_per_row' => 0, // Will be calculated when image is uploaded
                    'image_path' => "tilesets/{$tilesetName}.png",
                    '_missing_image' => true,
                    '_requires_upload' => true,
                ];
            } else {
                throw new \Exception("Tileset image file not found: {$tilesetName}");
            }
        }

        // Parse image information
        $imageInfo = $this->parseTilesetFromImage($imageFile);
        $maxTileId = !empty($tileIds) ? max($tileIds) : 0;

        return [
            'uuid' => $this->generateTilesetUuid($tilesetName),
            'name' => $tilesetName,
            'image_width' => $imageInfo['image_width'],
            'image_height' => $imageInfo['image_height'],
            'tile_width' => $imageInfo['tile_width'],
            'tile_height' => $imageInfo['tile_height'],
            'tiles_per_row' => $imageInfo['tiles_per_row'],
            'image_path' => "tilesets/{$tilesetName}.png",
        ];
    }

    /**
     * Create or update a tileset in the database.
     */
    public function createOrUpdateTileset(array $tilesetData, ?string $imageFile = null): TileSet
    {
        $existingTileset = null;
        
        if (isset($tilesetData['uuid'])) {
            $existingTileset = $this->tilesetRepository->findByUuid($tilesetData['uuid']);
        }

        if ($existingTileset) {
            // Update existing tileset
            $this->tilesetRepository->update($existingTileset, $tilesetData);
            return $existingTileset->fresh();
        } else {
            // Create new tileset
            return $this->tilesetRepository->create($tilesetData);
        }
    }

    /**
     * Convert tile ID to tile coordinates.
     */
    public function convertTileIdToCoordinates(int $tileId, int $tilesPerRow): array
    {
        if ($tilesPerRow <= 0) {
            throw new \Exception("Invalid tilesPerRow: {$tilesPerRow}");
        }

        $adjustedTileId = $tileId - 1; // Convert from 1-based to 0-based
        return [
            'tileX' => $adjustedTileId % $tilesPerRow,
            'tileY' => (int) ($adjustedTileId / $tilesPerRow),
        ];
    }

    /**
     * Generate a deterministic UUID for a tileset name.
     */
    public function generateTilesetUuid(string $tilesetName): string
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
    public function formatTilesetName(string $tilesetName): string
    {
        // Keep the original name format for legacy tilesets
        return $tilesetName;
    }

    /**
     * Build tileset suggestions for the import wizard.
     */
    public function buildTilesetSuggestions(array $tilesetUsage): array
    {
        $tilesets = [];
        
        foreach ($tilesetUsage as $tilesetName => $tileIds) {
            $formattedName = $this->formatTilesetName($tilesetName);
            
            // Try to find existing tileset by name
            $existingTileset = $this->findExistingTileset($tilesetName, $formattedName);
            
            // Check if tileset image exists
            $imageExists = $this->checkTilesetImageExists($tilesetName);
            
            // Use existing tileset UUID if available, otherwise null for new tilesets
            $uuid = $existingTileset ? $existingTileset->uuid : null;
            
            $tilesets[] = [
                'original_name' => $tilesetName,
                'formatted_name' => $formattedName,
                'tile_count' => count($tileIds),
                'max_tile_id' => max($tileIds),
                'image_exists' => $imageExists,
                'existing_tileset' => $existingTileset ? [
                    'uuid' => $existingTileset->uuid,
                    'name' => $existingTileset->name,
                    'image_path' => $existingTileset->image_path,
                ] : null,
                'requires_upload' => !$imageExists && !$existingTileset,
            ];
        }
        
        return $tilesets;
    }

    /**
     * Scan tileset usage from map data.
     */
    public function scanTilesetUsage(array $mapData, array $layerMappings): array
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
} 