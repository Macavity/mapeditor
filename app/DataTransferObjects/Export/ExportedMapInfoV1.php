<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Export;

use App\Models\TileMap;
use Carbon\Carbon;

readonly class ExportedMapInfoV1
{
    public function __construct(
        public string $uuid,
        public string $name,
        public int $width,
        public int $height,
        public int $tile_width,
        public int $tile_height,
        public ?ExportedCreatorV1 $creator = null,
    ) {}

    /**
     * Create from TileMap model.
     */
    public static function fromModel(TileMap $map): self
    {
        return new self(
            uuid: $map->uuid,
            name: $map->name,
            width: $map->width,
            height: $map->height,
            tile_width: $map->tile_width,
            tile_height: $map->tile_height,
            creator: $map->creator ? ExportedCreatorV1::fromModel($map->creator) : null,
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
            width: (int) $data['width'],
            height: (int) $data['height'],
            tile_width: (int) $data['tile_width'],
            tile_height: (int) $data['tile_height'],
            creator: isset($data['creator']) ? ExportedCreatorV1::fromArray($data['creator']) : null,
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
            'width' => $this->width,
            'height' => $this->height,
            'tile_width' => $this->tile_width,
            'tile_height' => $this->tile_height,
            'creator' => $this->creator?->toArray(),
        ];
    }

    /**
     * Get total tile count.
     */
    public function getTotalTiles(): int
    {
        return $this->width * $this->height;
    }



    /**
     * Check if the map has a creator.
     */
    public function hasCreator(): bool
    {
        return $this->creator !== null;
    }
} 