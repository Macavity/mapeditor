<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Export;

use App\Models\TileSet;
use Carbon\Carbon;

readonly class ExportedTilesetV1
{
    public function __construct(
        public string $uuid,
        public string $name,
        public int $image_width,
        public int $image_height,
        public int $tile_width,
        public int $tile_height,
        public int $tile_count,
        public ?string $image_url,
        public ?string $image_path,
        public int $margin,
        public int $spacing,
    ) {}

    /**
     * Create from TileSet model.
     */
    public static function fromModel(TileSet $tileset): self
    {
        return new self(
            uuid: $tileset->uuid,
            name: $tileset->name,
            image_width: $tileset->image_width,
            image_height: $tileset->image_height,
            tile_width: $tileset->tile_width,
            tile_height: $tileset->tile_height,
            tile_count: $tileset->tile_count,
            image_url: $tileset->image_url,
            image_path: $tileset->image_path,
            margin: $tileset->margin,
            spacing: $tileset->spacing,
        );
    }

    /**
     * Create from array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            uuid: $data['uuid'],
            name: $data['name'],
            image_width: (int) $data['image_width'],
            image_height: (int) $data['image_height'],
            tile_width: (int) $data['tile_width'],
            tile_height: (int) $data['tile_height'],
            tile_count: (int) $data['tile_count'],
            image_url: $data['image_url'] ?? null,
            image_path: $data['image_path'] ?? null,
            margin: (int) $data['margin'],
            spacing: (int) $data['spacing'],
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'image_width' => $this->image_width,
            'image_height' => $this->image_height,
            'tile_width' => $this->tile_width,
            'tile_height' => $this->tile_height,
            'tile_count' => $this->tile_count,
            'image_url' => $this->image_url,
            'image_path' => $this->image_path,
            'margin' => $this->margin,
            'spacing' => $this->spacing,
        ];
    }

    /**
     * Get tiles per row.
     */
    public function getTilesPerRow(): int
    {
        if ($this->tile_width === 0) {
            return 0;
        }

        $usableWidth = $this->image_width - (2 * $this->margin);
        return (int) floor($usableWidth / ($this->tile_width + $this->spacing));
    }

    /**
     * Get tiles per column.
     */
    public function getTilesPerColumn(): int
    {
        if ($this->tile_height === 0) {
            return 0;
        }

        $usableHeight = $this->image_height - (2 * $this->margin);
        return (int) floor($usableHeight / ($this->tile_height + $this->spacing));
    }



    /**
     * Check if tileset has an image URL.
     */
    public function hasImageUrl(): bool
    {
        return $this->image_url !== null;
    }

    /**
     * Check if tileset has an image path.
     */
    public function hasImagePath(): bool
    {
        return $this->image_path !== null;
    }
} 