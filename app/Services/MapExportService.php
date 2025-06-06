<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TileMap;
use App\Repositories\MapRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class MapExportService
{
    /**
     * Prepare the map data for export.
     */
    public function prepareExportData(TileMap $map): array
    {
        $mapRepository = app(MapRepository::class);
        $usedTilesets = $mapRepository->getUsedTilesets($map);

        return [
            'exported_at' => Carbon::now()->toISOString(),
            'export_version' => '1.0',
            'map' => [
                'uuid' => $map->uuid,
                'name' => $map->name,
                'width' => $map->width,
                'height' => $map->height,
                'tile_width' => $map->tile_width,
                'tile_height' => $map->tile_height,
                'created_at' => $map->created_at->toISOString(),
                'updated_at' => $map->updated_at->toISOString(),
                'creator' => $map->creator ? [
                    'id' => $map->creator->id,
                    'name' => $map->creator->name,
                    'email' => $map->creator->email,
                ] : null,
            ],
            'tilesets' => $usedTilesets->map(function ($tileset) {
                return [
                    'uuid' => $tileset->uuid,
                    'name' => $tileset->name,
                    'image_width' => $tileset->image_width,
                    'image_height' => $tileset->image_height,
                    'tile_width' => $tileset->tile_width,
                    'tile_height' => $tileset->tile_height,
                    'image_url' => $tileset->image_url,
                    'image_path' => $tileset->image_path,
                    'tile_count' => $tileset->tile_count,
                    'first_gid' => $tileset->first_gid,
                    'margin' => $tileset->margin,
                    'spacing' => $tileset->spacing,
                    'created_at' => $tileset->created_at->toISOString(),
                    'updated_at' => $tileset->updated_at->toISOString(),
                ];
            })->values()->toArray(),
            'layers' => $map->layers->sortBy('z')->map(function ($layer) {
                return [
                    'uuid' => $layer->uuid,
                    'name' => $layer->name,
                    'type' => $layer->type->value,
                    'x' => $layer->x,
                    'y' => $layer->y,
                    'z' => $layer->z,
                    'width' => $layer->width,
                    'height' => $layer->height,
                    'visible' => $layer->visible,
                    'opacity' => $layer->opacity,
                    'data' => $layer->data,
                    'created_at' => $layer->created_at->toISOString(),
                    'updated_at' => $layer->updated_at->toISOString(),
                ];
            })->values()->toArray(),
        ];
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
    public function exportAsJson(array $data, string $path): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        if ($json === false) {
            throw new \RuntimeException('Failed to encode data as JSON');
        }

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