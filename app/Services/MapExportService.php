<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TileMap;
use App\Repositories\MapRepository;
use App\DataTransferObjects\Export\ExportMapFormatV1;
use App\DataTransferObjects\Export\ExportedMapInfoV1;
use App\DataTransferObjects\Export\ExportedTilesetV1;
use App\DataTransferObjects\Export\ExportedLayerV1;
use App\Constants\ExportVersions;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class MapExportService
{
    /**
     * Prepare the map data for export.
     */
    public function prepareExportData(TileMap $map): ExportMapFormatV1
    {
        $mapRepository = app(MapRepository::class);
        $usedTilesets = $mapRepository->getUsedTilesets($map);

        // Create map info DTO
        $mapInfo = ExportedMapInfoV1::fromModel($map);

        // Create tileset DTOs
        $tilesets = $usedTilesets->map(fn($tileset) => ExportedTilesetV1::fromModel($tileset))->values()->toArray();

        // Create layer DTOs (sorted by z-index)
        $layers = $map->layers->sortBy('z')->map(fn($layer) => ExportedLayerV1::fromModel($layer))->values()->toArray();

        return new ExportMapFormatV1(
            export_version: ExportVersions::getCurrent(),
            map: $mapInfo,
            tilesets: $tilesets,
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
        return "exports/maps/{$filename}";
    }

    /**
     * Export data as JSON.
     */
    public function exportAsJson(ExportMapFormatV1 $exportData, string $path): void
    {
        $json = $exportData->toJson();
        Storage::put($path, $json);
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
        return in_array($format, ['json']);
    }

    /**
     * Get supported export formats.
     */
    public function getSupportedFormats(): array
    {
        return ['json'];
    }
} 