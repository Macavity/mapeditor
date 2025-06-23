<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TileSet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class TileSetRepository
{
    /**
     * Find a tileset by UUID.
     */
    public function findByUuid(string $uuid): ?TileSet
    {
        return TileSet::where('uuid', $uuid)->first();
    }

    /**
     * Find a tileset by name (exact match).
     */
    public function findByName(string $name): ?TileSet
    {
        return TileSet::where('name', $name)->first();
    }

    /**
     * Find a tileset by name variations (fuzzy match).
     */
    public function findByNameVariations(string $originalName, string $formattedName): ?TileSet
    {
        $searchNames = [
            $formattedName,        // "Castle Exterior Mc"
            $originalName,         // "castle_exterior_mc"
            ucwords($originalName, '_'), // "Castle_Exterior_Mc"
            str_replace('_', ' ', $originalName), // "castle exterior mc"
            ucfirst(str_replace('_', ' ', $originalName)), // "Castle exterior mc"
        ];

        foreach ($searchNames as $searchName) {
            $tileset = TileSet::where('name', 'LIKE', $searchName)->first();
            if ($tileset) {
                return $tileset;
            }
        }

        return null;
    }

    /**
     * Create a new tileset.
     */
    public function create(array $data): TileSet
    {
        return TileSet::create($data);
    }

    /**
     * Update an existing tileset.
     */
    public function update(TileSet $tileset, array $data): bool
    {
        return $tileset->update($data);
    }

    /**
     * Delete a tileset.
     */
    public function delete(TileSet $tileset): bool
    {
        return $tileset->delete();
    }

    /**
     * Get all tilesets with optional filtering.
     */
    public function findAll(
        ?int $limit = null,
        ?string $nameFilter = null,
        string $orderBy = 'name',
        string $orderDirection = 'asc'
    ): Collection {
        $query = TileSet::query();

        if ($nameFilter) {
            $query->where('name', 'like', "%{$nameFilter}%");
        }

        $query->orderBy($orderBy, $orderDirection);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Check if a tileset exists by UUID.
     */
    public function existsByUuid(string $uuid): bool
    {
        return TileSet::where('uuid', $uuid)->exists();
    }

    /**
     * Check if a tileset exists by name.
     */
    public function existsByName(string $name): bool
    {
        return TileSet::where('name', $name)->exists();
    }

    /**
     * Count total tilesets.
     */
    public function count(): int
    {
        return TileSet::count();
    }

    /**
     * Get recently created tilesets.
     */
    public function getRecentlyCreated(int $limit = 10): Collection
    {
        return TileSet::orderBy('created_at', 'desc')
                     ->limit($limit)
                     ->get();
    }

    /**
     * Get tilesets that have image files.
     */
    public function getWithImages(): Collection
    {
        return TileSet::whereNotNull('image_path')->get();
    }

    /**
     * Get tilesets that are missing image files.
     */
    public function getWithoutImages(): Collection
    {
        return TileSet::whereNull('image_path')->get();
    }

    /**
     * Create a tileset from wizard upload with proper type safety.
     */
    public function createFromWizardUpload(
        string $name,
        string $imagePath,
        int $imageWidth,
        int $imageHeight,
        int $tileWidth = 32,
        int $tileHeight = 32,
        int $firstGid = 1,
        int $margin = 0,
        int $spacing = 0
    ): TileSet {
        $tileCount = intval($imageWidth / $tileWidth) * intval($imageHeight / $tileHeight);
        
        return $this->create([
            'name' => $name,
            'image_path' => $imagePath,
            'image_width' => $imageWidth,
            'image_height' => $imageHeight,
            'tile_width' => $tileWidth,
            'tile_height' => $tileHeight,
            'tile_count' => $tileCount,
            'first_gid' => $firstGid,
            'margin' => $margin,
            'spacing' => $spacing,
        ]);
    }
} 