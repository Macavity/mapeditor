<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TileMap;
use Carbon\Carbon;

class MapDisplayService
{
    /**
     * Truncate a string to specified length.
     */
    public function truncate(string $string, int $length): string
    {
        return strlen($string) > $length 
            ? substr($string, 0, $length - 3) . '...' 
            : $string;
    }

    /**
     * Format layer counts for display.
     */
    public function formatLayerCounts(array $layerStats): string
    {
        $parts = [];
        
        if ($layerStats['by_type']['background'] > 0) {
            $parts[] = $layerStats['by_type']['background'] . 'bg';
        }
        if ($layerStats['by_type']['floor'] > 0) {
            $parts[] = $layerStats['by_type']['floor'] . 'fl';
        }
        if ($layerStats['by_type']['sky'] > 0) {
            $parts[] = $layerStats['by_type']['sky'] . 'sky';
        }

        return empty($parts) ? 'none' : implode(', ', $parts);
    }

    /**
     * Format UUID for display (shortened).
     */
    public function formatUuid(string $uuid, int $length = 8): string
    {
        return substr($uuid, 0, $length) . '...';
    }

    /**
     * Format map dimensions for display.
     */
    public function formatDimensions(TileMap $map): string
    {
        return "{$map->width}x{$map->height}";
    }

    /**
     * Format tile size for display.
     */
    public function formatTileSize(TileMap $map): string
    {
        return "{$map->tile_width} x {$map->tile_height} pixels";
    }

    /**
     * Format date for display.
     */
    public function formatDate(Carbon $date, string $format = 'Y-m-d H:i'): string
    {
        return $date->format($format);
    }

    /**
     * Get layer type display name.
     */
    public function getLayerTypeDisplayName(string $type): string
    {
        return match($type) {
            'background' => 'Background',
            'floor' => 'Floor',
            'sky' => 'Sky',
            'field_type' => 'Field Type',
            default => ucfirst($type),
        };
    }

    /**
     * Format boolean for display.
     */
    public function formatBoolean(bool $value): string
    {
        return $value ? 'Yes' : 'No';
    }

    /**
     * Format opacity for display.
     */
    public function formatOpacity(float $opacity): string
    {
        return number_format($opacity, 2);
    }

    /**
     * Prepare table row data for map listing.
     */
    public function prepareMapListRow(TileMap $map, array $layerStats): array
    {
        // Prioritize database creator, fall back to external creator, then 'Unknown'
        $creatorName = $map->creator?->name ?? $map->external_creator ?? 'Unknown';
        
        return [
            $this->formatUuid($map->uuid),
            $this->truncate($map->name, 25),
            $this->formatDimensions($map),
            $this->formatLayerCounts($layerStats),
            $this->truncate($creatorName, 15),
            $this->formatDate($map->updated_at),
        ];
    }

    /**
     * Prepare table row data for layer details.
     */
    public function prepareLayerDetailRow($layer): array
    {
        $tileCount = is_array($layer->data) ? count($layer->data) : 0;
        
        return [
            $layer->name,
            $this->getLayerTypeDisplayName($layer->type->value),
            $layer->z,
            "{$layer->width}x{$layer->height}",
            $tileCount,
            $this->formatBoolean($layer->visible),
            $this->formatOpacity($layer->opacity),
        ];
    }
} 