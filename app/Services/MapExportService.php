<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TileMap;
use App\DataTransferObjects\Export\ExportMapFormatV1;
use App\DataTransferObjects\Export\ExportedMapInfoV1;
use App\DataTransferObjects\Export\ExportedTilesetV1;
use App\DataTransferObjects\Export\ExportedLayerV1;
use App\Constants\ExportVersions;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Models\TileSet;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\OutputInterface;
use App\ValueObjects\Tile;

class MapExportService
{
    private OutputInterface $output;
    private string $basePath;

    public function __construct(OutputInterface $output, string $basePath = 'exports')
    {
        $this->output = $output;
        $this->basePath = $basePath;
    }

    /**
     * Extract tileset UUIDs from layer data.
     */
    private function extractTilesetUuids(array $layers): array
    {
        $tilesetUuids = collect();
        $this->output->info('Starting tileset extraction from ' . count($layers) . ' layers');

        foreach ($layers as $layer) {
            
            if (is_array($layer->data)) {
                foreach ($layer->data as $tile) {
                    if ($tile instanceof Tile) {
                        $tilesetUuid = $tile->brush->tileset;
                        $tilesetUuids->push($tilesetUuid);
                    } else {
                        $this->output->writeln('<error>Invalid tile data in layer: ' . $layer->name . '</error>');
                    }
                }
            } else {
                $this->output->error('Layer data is not an array: ' . gettype($layer->data));
            }
        }

        $uniqueTilesetUuids = $tilesetUuids->unique()->values()->toArray();
        $this->output->writeln('Found ' . count($uniqueTilesetUuids) . ' unique tileset UUIDs');
        return $uniqueTilesetUuids;
    }

    /**
     * Prepare the map data for export.
     */
    public function prepareExportData(TileMap $map): ExportMapFormatV1
    {
        // Create map info DTO
        $mapInfo = ExportedMapInfoV1::fromModel($map);

        // Extract tileset UUIDs and get tilesets
        $tilesetUuids = $this->extractTilesetUuids($map->layers->all());
        $tilesets = TileSet::whereIn('uuid', $tilesetUuids)->get();
        
        // Create tileset DTOs
        $tilesetDtos = $tilesets->map(fn($tileset) => ExportedTilesetV1::fromModel($tileset))->values()->toArray();

        // Create layer DTOs (sorted by z-index)
        $layers = $map->layers->sortBy('z')->map(fn($layer) => ExportedLayerV1::fromModel($layer))->values()->toArray();

        return new ExportMapFormatV1(
            export_version: ExportVersions::getCurrent(),
            map: $mapInfo,
            tilesets: $tilesetDtos,
            layers: $layers,
        );
    }

    /**
     * Generate a safe filename for export.
     */
    public function generateFilename(TileMap $map, string $format = 'json'): string
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $map->name);
        
        return "{$sanitizedName}_{$timestamp}.{$format}";
    }

    /**
     * Get the default export path for a filename.
     */
    public function getDefaultExportPath(string $filename): string
    {
        return "{$this->basePath}/{$filename}";
    }

    /**
     * Ensure the export directory structure exists.
     */
    private function ensureExportDirectories(): void
    {
        Storage::makeDirectory($this->basePath);
        Storage::makeDirectory("{$this->basePath}/tilesets");
    }

    /**
     * Export data as JSON.
     */
    public function exportAsJson(ExportMapFormatV1 $exportData, string $path): void
    {
        $this->ensureExportDirectories();
        $json = $exportData->toJson();
        Storage::put($path, $json);
        $this->output->info('Stored JSON file at: ' . Storage::path($path));
    }

    /**
     * Get the full filesystem path for a storage path.
     */
    public function getFullPath(string $storagePath): string
    {
        return Storage::path($storagePath);
    }

    /**
     * Validate export format.
     */
    public function isValidFormat(string $format): bool
    {
        return in_array($format, $this->getSupportedFormats());
    }

    /**
     * Get supported export formats.
     */
    public function getSupportedFormats(): array
    {
        return ['json', 'tmx'];
    }

    /**
     * Copy a tileset image to storage and return its new path.
     */
    private function copyTilesetImage(string $imagePath): string
    {
        $publicDisk = Storage::disk('public');
        $filename = basename($imagePath);
        $storagePath = "{$this->basePath}/tilesets/{$filename}";
        $relativePath = "tilesets/{$filename}";

        $this->output->writeln("Processing tileset image: {$imagePath}");

        if (!$publicDisk->exists($imagePath)) {
            $this->output->error("Source tileset file not found: {$imagePath}");
            throw new \RuntimeException("Tileset file not found: {$imagePath}");
        }

        if (!Storage::exists($storagePath)) {
            $this->output->info("Copying tileset image: {$filename}");
            Storage::put(
                $storagePath, 
                $publicDisk->get($imagePath)
            );
        }

        return $relativePath;
    }

    /**
     * Export the map data as a TMX file.
     *
     * @param ExportMapFormatV1 $exportData
     * @param string $exportPath
     * @return void
     */
    public function exportAsTmx(ExportMapFormatV1 $exportData, string $exportPath): void
    {
        $this->ensureExportDirectories();
        $map = $exportData->map;
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $mapElement = $dom->createElement('map');
        $mapElement->setAttribute('version', '1.10');
        $mapElement->setAttribute('tiledversion', '1.11.2');
        $mapElement->setAttribute('orientation', 'orthogonal');
        $mapElement->setAttribute('renderorder', 'left-down');
        $mapElement->setAttribute('width', (string)$map->width);
        $mapElement->setAttribute('height', (string)$map->height);
        $mapElement->setAttribute('tilewidth', (string)$map->tile_width);
        $mapElement->setAttribute('tileheight', (string)$map->tile_height);
        $mapElement->setAttribute('infinite', '0');
        $mapElement->setAttribute('nextlayerid', (string)(count($exportData->layers) + 1));
        $mapElement->setAttribute('nextobjectid', '1');
        $dom->appendChild($mapElement);

        // Calculate firstgid for each tileset
        $tilesetGids = [];
        $currentGid = 1;
        foreach ($exportData->tilesets as $tileset) {
            $tilesetGids[$tileset->uuid] = $currentGid;
            $currentGid += $tileset->tile_count;
        }

        // Add tileset elements
        foreach ($exportData->tilesets as $tileset) {
            $columns = (int)($tileset->image_width / $tileset->tile_width);
            $this->output->info("Tileset {$tileset->name}: calculated columns = {$columns} (image_width: {$tileset->image_width}, tile_width: {$tileset->tile_width})");

            $tilesetElement = $dom->createElement('tileset');
            $tilesetElement->setAttribute('firstgid', (string)$tilesetGids[$tileset->uuid]);
            $tilesetElement->setAttribute('name', $tileset->name);
            $tilesetElement->setAttribute('tilewidth', (string)$tileset->tile_width);
            $tilesetElement->setAttribute('tileheight', (string)$tileset->tile_height);
            $tilesetElement->setAttribute('tilecount', (string)$tileset->tile_count);
            $tilesetElement->setAttribute('columns', (string)$columns);
            $mapElement->appendChild($tilesetElement);

            // Copy tileset image to storage and update the path
            $storagePath = $this->copyTilesetImage($tileset->image_path);

            $imageElement = $dom->createElement('image');
            $imageElement->setAttribute('source', $storagePath);
            $imageElement->setAttribute('width', (string)$tileset->image_width);
            $imageElement->setAttribute('height', (string)$tileset->image_height);
            $tilesetElement->appendChild($imageElement);
        }

        // Add layer elements
        foreach ($exportData->layers as $index => $layer) {
            $layerElement = $dom->createElement('layer');
            $layerElement->setAttribute('id', (string)($index + 1));
            $layerElement->setAttribute('name', $layer->name);
            $layerElement->setAttribute('width', (string)$map->width);
            $layerElement->setAttribute('height', (string)$map->height);
            $mapElement->appendChild($layerElement);

            $dataElement = $dom->createElement('data');
            $dataElement->setAttribute('encoding', 'csv');
            $layerElement->appendChild($dataElement);

            // Create a grid of zeros with the correct dimensions
            $grid = array_fill(0, $map->height, array_fill(0, $map->width, 0));

            // Place tiles in their correct positions
            foreach ($layer->data as $tile) {
                if ($tile instanceof Tile && $tile->brush->tileset) {
                    $tileset = collect($exportData->tilesets)->firstWhere('uuid', $tile->brush->tileset);
                    if ($tileset) {
                        $columns = (int)($tileset->image_width / $tileset->tile_width);
                        $gid = $tilesetGids[$tileset->uuid] + $tile->brush->tileX + $tile->brush->tileY * $columns;
                        $grid[$tile->y][$tile->x] = $gid;
                    }
                }
            }

            // Convert grid to CSV format
            $rows = array_map(fn($row) => implode(',', $row), $grid);
            $csvContent = implode(",\n", $rows);

            $dataElement->textContent = "\n" . $csvContent . "\n";
        }

        $xml = $dom->saveXML();
        if ($xml === false) {
            throw new \RuntimeException('Failed to generate TMX XML.');
        }
        
        Storage::put($exportPath, $xml);
        $this->output->info('Stored TMX file at: ' . Storage::path($exportPath));
    }
} 