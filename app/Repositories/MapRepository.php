<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TileMap;
use App\Models\TileSet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class MapRepository
{
    /**
     * Find a map by full or partial UUID.
     */
    public function findByUuid(string $uuid, bool $withRelations = true): ?TileMap
    {
        $query = TileMap::query();

        if ($withRelations) {
            $query->with(['creator', 'layers']);
        }

        return $query->where('uuid', 'like', $uuid . '%')->first();
    }

    /**
     * Find a map by exact UUID.
     */
    public function findByExactUuid(string $uuid, bool $withRelations = true): ?TileMap
    {
        $query = TileMap::query();

        if ($withRelations) {
            $query->with(['creator', 'layers']);
        }

        return $query->where('uuid', $uuid)->first();
    }

    /**
     * Get all maps with optional filtering and limiting.
     */
    public function findAll(
        ?int $limit = null,
        ?string $creatorFilter = null,
        bool $withRelations = true,
        string $orderBy = 'updated_at',
        string $orderDirection = 'desc'
    ): Collection {
        $query = TileMap::query();

        if ($withRelations) {
            $query->with(['creator', 'layers']);
        }

        // Apply creator filter if provided
        if ($creatorFilter) {
            $query->whereHas('creator', function ($q) use ($creatorFilter) {
                $q->where('name', 'like', "%{$creatorFilter}%")
                  ->orWhere('email', 'like', "%{$creatorFilter}%");
            });
        }

        $query->orderBy($orderBy, $orderDirection);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Check if a map exists by UUID (partial match).
     */
    public function existsByUuid(string $uuid): bool
    {
        return TileMap::where('uuid', 'like', $uuid . '%')->exists();
    }

    /**
     * Check if a map exists by exact UUID.
     */
    public function existsByExactUuid(string $uuid): bool
    {
        return TileMap::where('uuid', $uuid)->exists();
    }

    /**
     * Get maps by creator.
     */
    public function findByCreator(int $creatorId, bool $withRelations = true): Collection
    {
        $query = TileMap::where('creator_id', $creatorId);

        if ($withRelations) {
            $query->with(['creator', 'layers']);
        }

        return $query->orderBy('updated_at', 'desc')->get();
    }

    /**
     * Get layer statistics for a map.
     */
    public function getLayerStats(TileMap $map): array
    {
        $layerCounts = $map->layers->groupBy('type')->map->count();
        
        return [
            'total' => $map->layers->count(),
            'by_type' => [
                'background' => $layerCounts->get('background', 0),
                'floor' => $layerCounts->get('floor', 0),
                'sky' => $layerCounts->get('sky', 0),
                'field_type' => $layerCounts->get('field_type', 0),
            ],
            'total_tiles' => $map->layers->sum(function ($layer) {
                return is_array($layer->data) ? count($layer->data) : 0;
            }),
        ];
    }

    /**
     * Count total maps.
     */
    public function count(): int
    {
        return TileMap::count();
    }

    /**
     * Get recently updated maps.
     */
    public function getRecentlyUpdated(int $limit = 10, bool $withRelations = true): Collection
    {
        $query = TileMap::query();

        if ($withRelations) {
            $query->with(['creator', 'layers']);
        }

        return $query->orderBy('updated_at', 'desc')
                    ->limit($limit)
                    ->get();
    }
} 