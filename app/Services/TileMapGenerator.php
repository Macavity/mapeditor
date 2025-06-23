<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Layer;
use App\Models\TileMap;
use App\Models\TileSet;
use App\ValueObjects\Tile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Interfaces\ImageInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Sentry\Severity;
use Sentry\Laravel\Facade as Sentry;
use RuntimeException;

class TileMapGenerator
{
    private const MAPS_BASE_DIRECTORY = 'maps';
    private const LAYER_IMAGE_FORMAT = 'png';
    private const LAYER_IMAGE_QUALITY = 100;
    private const DIRECTORY_PERMISSIONS = 0755;
    private const TRANSPARENT_COLOR = 'rgba(0, 0, 0, 0)';

    public function __construct(
        private readonly ImageManager $imageManager = new ImageManager(new Driver())
    ) {}

    public function generateMapImage(TileMap $tileMap): void
    {
        $this->configureImageProcessing();
        
        try {
            $layers = $this->getVisibleLayers($tileMap);
            
            if ($layers->isEmpty()) {
                Log::warning("No visible layers found for map ID: {$tileMap->id}");
                return;
            }
            
            $baseDirectory = $this->createMapDirectory($tileMap);
            Log::info("Processing map: {$tileMap->name} (ID: {$tileMap->id}) with {$layers->count()} layers");
            
            $this->processLayers($layers, $tileMap, $baseDirectory);
            
        } catch (RuntimeException $e) {
            Log::error("Error generating map image for map ID {$tileMap->id}: " . $e->getMessage(), [
                'exception' => $e,
                'map' => $tileMap->toArray(),
            ]);
            throw $e;
        }
    }

    public function generateLayerImage(Layer $layer): void
    {
        $this->configureImageProcessing();
        
        try {
            if (!$layer->visible) {
                Log::warning("Layer ID {$layer->id} is not visible, skipping generation");
                return;
            }
            
            $baseDirectory = $this->createMapDirectory($layer->tileMap);
            Log::info("Generating single layer image: {$layer->name} (ID: {$layer->id})");
            
            $this->processLayer($layer, $layer->tileMap, $baseDirectory);
            
        } catch (RuntimeException $e) {
            Log::error("Error generating layer image for layer ID {$layer->id}: " . $e->getMessage(), [
                'exception' => $e,
                'layer' => $layer->toArray(),
            ]);
            throw $e;
        }
    }

    public function layerImageExists(Layer $layer): bool
    {
        if (!$layer->image_path) {
            return false;
        }
        
        return Storage::disk('public')->exists($layer->image_path);
    }

    public function isLayerImageUpToDate(Layer $layer): bool
    {
        if (!$this->layerImageExists($layer)) {
            return false;
        }
        
        // Check if the layer has been modified since the image was created
        $imagePath = Storage::disk('public')->path($layer->image_path);
        $imageModifiedTime = filemtime($imagePath);
        $layerUpdatedTime = $layer->updated_at->getTimestamp();
        
        return $imageModifiedTime >= $layerUpdatedTime;
    }

    private function configureImageProcessing(): void
    {
        // Configure GD to handle PNGs correctly
        $originalErrorLevel = error_reporting();
        error_reporting($originalErrorLevel & ~E_WARNING);
        
        // Disable PNG warnings
        ini_set('gd.jpeg_ignore_warning', '1');
    }

    private function getVisibleLayers(TileMap $tileMap): Collection
    {
        return $tileMap->layers()
            ->where('visible', true)
            ->get();
    }

    private function createMapDirectory(TileMap $tileMap): string
    {
        $baseDirectory = self::MAPS_BASE_DIRECTORY . "/{$tileMap->id}";
        Storage::makeDirectory($baseDirectory);
        
        return $baseDirectory;
    }

    private function processLayers(Collection $layers, TileMap $tileMap, string $baseDirectory): void
    {
        foreach ($layers as $layer) {
            $this->processLayer($layer, $tileMap, $baseDirectory);
        }
    }

    private function processLayer(Layer $layer, TileMap $tileMap, string $baseDirectory): void
    {
        try {
            // Log::debug("Processing layer: {$layer->name} (ID: {$layer->id})");
            
            $image = $this->createLayerImage($tileMap);
            $this->renderLayer($image, $layer, $tileMap->tile_width, $tileMap->tile_height);
            
            $imagePath = $this->saveLayerImage($image, $layer, $baseDirectory);
            $this->updateLayerImagePath($layer, $imagePath);
            
            Log::info("Successfully generated image for layer: {$layer->name} (ID: {$layer->id})");
            
        } catch (RuntimeException $e) {
            Log::error("Error processing layer ID {$layer->id}: " . $e->getMessage(), [
                'exception' => $e,
                'layer' => $layer->toArray(),
            ]);
            throw new RuntimeException(
                "Failed to process layer {$layer->name} (ID: {$layer->id}): " . $e->getMessage(), 
                0, 
                $e
            );
        }
    }

    private function createLayerImage(TileMap $tileMap): ImageInterface
    {
        $width = $tileMap->width * $tileMap->tile_width;
        $height = $tileMap->height * $tileMap->tile_height;
        
        Log::debug("Creating image with dimensions: {$width}x{$height}");
        $image = $this->imageManager->create($width, $height);
        
        Log::debug("Setting transparent background");
        $image->fill(self::TRANSPARENT_COLOR);
        
        return $image;
    }

    private function saveLayerImage(ImageInterface $image, Layer $layer, string $baseDirectory): string
    {
        $relativePath = "{$baseDirectory}/layer_{$layer->id}." . self::LAYER_IMAGE_FORMAT;
        
        // Use Storage::disk('public') to ensure proper path handling
        $fullPath = Storage::disk('public')->path($relativePath);
        
        $this->ensureDirectoryExists($fullPath);
        
        Log::debug("Saving image to: {$fullPath}");
        $image->save($fullPath, self::LAYER_IMAGE_QUALITY);
        
        return $relativePath;
    }

    private function ensureDirectoryExists(string $filePath): void
    {
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, self::DIRECTORY_PERMISSIONS, true);
        }
    }

    private function updateLayerImagePath(Layer $layer, string $imagePath): void
    {
        $layer->update(['image_path' => $imagePath]);
    }

    private function renderLayer(ImageInterface $image, Layer $layer, int $tileWidth, int $tileHeight): void
    {
        if (empty($layer->data)) {
            Log::debug("No tile data found for layer ID: {$layer->id}");
            // Still save a blank image for empty layers
            return;
        }
        
        Log::debug(sprintf("Rendering layer ID %d with %d tiles", $layer->id, count($layer->data)));
        
        $tilesets = $this->loadTilesetsForLayer($layer);
        
        if ($tilesets->isEmpty()) {
            Log::warning("No valid tilesets found in layer {$layer->id}");
            return;
        }
        
        $this->renderTiles($image, $layer, $tilesets, $tileWidth, $tileHeight);
    }

    private function loadTilesetsForLayer(Layer $layer): Collection
    {
        $tilesetUuids = $this->extractTilesetUuids($layer);
        
        if (empty($tilesetUuids)) {
            return collect();
        }
        
        return TileSet::whereIn('uuid', $tilesetUuids)->get()->keyBy('uuid');
    }

    private function extractTilesetUuids(Layer $layer): array
    {
        return collect($layer->data)
            ->pluck('brush.tileset')
            ->unique()
            ->filter()
            ->values()
            ->toArray();
    }

    private function renderTiles(ImageInterface $image, Layer $layer, Collection $tilesets, int $tileWidth, int $tileHeight): void
    {
        foreach ($layer->data as $tile) {
            $this->renderTile($image, $tile, $tilesets, $tileWidth, $tileHeight);
        }
    }

    private function renderTile(ImageInterface $image, Tile $tile, Collection $tilesets, int $tileWidth, int $tileHeight): void
    {
        try {
            $tileset = $this->getTilesetForTile($tile, $tilesets);
            
            if (!$tileset) {
                return;
            }
            
            $tileImage = $this->createTileImage($tile, $tileset);
            
            if (!$tileImage) {
                return;
            }
            
            $this->placeTileOnImage($image, $tileImage, $tile, $tileWidth, $tileHeight);
            
            // $this->logTileRendering($tile);
            
        } catch (RuntimeException $e) {
            Log::error("Error rendering tile: " . $e->getMessage(), [
                'exception' => $e,
                'tile' => $tile,
            ]);
        }
    }

    private function getTilesetForTile(Tile $tile, Collection $tilesets): ?TileSet
    {
        $tileset = $tilesets->get($tile->brush->tileset);
        
        if (!$tileset) {
            $message = sprintf(
                "Tileset %s not found for tile at [%d, %d]",
                $tile->brush->tileset,
                $tile->x,
                $tile->y
            );
            Log::error($message);
            Sentry::captureMessage($message, Severity::error());
            return null;
        }
        
        return $tileset;
    }

    private function createTileImage(Tile $tile, TileSet $tileset): ?ImageInterface
    {
        $tilesetImagePath = $this->getTilesetImagePath($tileset);
        
        if (!$this->tilesetImageExists($tilesetImagePath)) {
            return null;
        }
        
        $tilesetImage = $this->loadTilesetImage($tilesetImagePath);
        
        if (!$tilesetImage) {
            return null;
        }
        
        return $this->extractTileFromTileset($tilesetImage, $tile, $tileset);
    }

    private function getTilesetImagePath(TileSet $tileset): string
    {
        return storage_path('app/public/' . $tileset->image_path);
    }

    private function tilesetImageExists(string $tilesetImagePath): bool
    {
        if (!file_exists($tilesetImagePath)) {
            Log::warning(sprintf("Tileset image not found: %s", $tilesetImagePath));
            return false;
        }
        
        return true;
    }

    private function loadTilesetImage(string $tilesetImagePath): ?ImageInterface
    {
        // Load the tileset image with error suppression for GD warnings
        $tilesetImage = @$this->imageManager->read($tilesetImagePath);
        
        if (!$tilesetImage) {
            Log::warning(sprintf("Failed to load tileset image: %s", $tilesetImagePath));
            return null;
        }
        
        return $tilesetImage;
    }

    private function extractTileFromTileset(ImageInterface $tilesetImage, Tile $tile, TileSet $tileset): ImageInterface
    {
        $sourceX = $tile->brush->tileX * $tileset->tile_width;
        $sourceY = $tile->brush->tileY * $tileset->tile_height;
        
        // Create a new true color image for the tile
        $tileImage = $this->imageManager->create(
            $tileset->tile_width,
            $tileset->tile_height
        )->fill(self::TRANSPARENT_COLOR);
        
        // Copy the tile from the tileset to our new image
        $tileImage->place(
            $tilesetImage,
            'top-left',
            -$sourceX,  // Negative offset to position the source correctly
            -$sourceY
        );
        
        // Ensure we have a clean alpha channel
        $tileImage->toPng();
        
        return $tileImage;
    }

    private function placeTileOnImage(ImageInterface $image, ImageInterface $tileImage, Tile $tile, int $tileWidth, int $tileHeight): void
    {
        $destX = $tile->x * $tileWidth;
        $destY = $tile->y * $tileHeight;
        
        // Paste the tile onto the layer image
        $image->place(
            $tileImage,
            'top-left',
            $destX,
            $destY
        );
    }

    private function logTileRendering(Tile $tile): void
    {
        Log::debug(sprintf(
            'Rendered tile at [%d, %d] from tileset %s at [%d, %d]',
            $tile->x,
            $tile->y,
            $tile->brush->tileset,
            $tile->brush->tileX,
            $tile->brush->tileY
        ));
    }
}
