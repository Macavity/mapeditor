<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Export;

use App\Models\Layer;
use App\Enums\LayerType;
use Carbon\Carbon;

readonly class ExportedLayerV1
{
    public function __construct(
        public string $uuid,
        public string $name,
        public string $type,
        public int $x,
        public int $y,
        public int $z,
        public int $width,
        public int $height,
        public bool $visible,
        public float $opacity,
        public array $data,
        public string $created_at,
        public string $updated_at,
    ) {}

    /**
     * Create from Layer model.
     */
    public static function fromModel(Layer $layer): self
    {
        return new self(
            uuid: $layer->uuid,
            name: $layer->name,
            type: $layer->type->value,
            x: $layer->x,
            y: $layer->y,
            z: $layer->z,
            width: $layer->width,
            height: $layer->height,
            visible: $layer->visible,
            opacity: $layer->opacity,
            data: $layer->data,
            created_at: $layer->created_at->toISOString(),
            updated_at: $layer->updated_at->toISOString(),
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
            type: $data['type'],
            x: (int) $data['x'],
            y: (int) $data['y'],
            z: (int) $data['z'],
            width: (int) $data['width'],
            height: (int) $data['height'],
            visible: (bool) $data['visible'],
            opacity: (float) $data['opacity'],
            data: $data['data'],
            created_at: $data['created_at'],
            updated_at: $data['updated_at'],
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
            'type' => $this->type,
            'x' => $this->x,
            'y' => $this->y,
            'z' => $this->z,
            'width' => $this->width,
            'height' => $this->height,
            'visible' => $this->visible,
            'opacity' => $this->opacity,
            'data' => $this->data,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get layer type as enum.
     */
    public function getType(): LayerType
    {
        return LayerType::from($this->type);
    }

    /**
     * Get tile count in this layer.
     */
    public function getTileCount(): int
    {
        return count($this->data);
    }

    /**
     * Get total possible tiles for this layer.
     */
    public function getTotalTileSlots(): int
    {
        return $this->width * $this->height;
    }

    /**
     * Get fill percentage (tiles used vs total slots).
     */
    public function getFillPercentage(): float
    {
        $totalSlots = $this->getTotalTileSlots();
        if ($totalSlots === 0) {
            return 0.0;
        }
        
        return ($this->getTileCount() / $totalSlots) * 100;
    }

    /**
     * Get created at as Carbon instance.
     */
    public function getCreatedAt(): Carbon
    {
        return Carbon::parse($this->created_at);
    }

    /**
     * Get updated at as Carbon instance.
     */
    public function getUpdatedAt(): Carbon
    {
        return Carbon::parse($this->updated_at);
    }

    /**
     * Check if layer is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * Check if layer is hidden.
     */
    public function isHidden(): bool
    {
        return !$this->visible;
    }

    /**
     * Check if layer is fully opaque.
     */
    public function isOpaque(): bool
    {
        return $this->opacity >= 1.0;
    }

    /**
     * Check if layer is transparent.
     */
    public function isTransparent(): bool
    {
        return $this->opacity < 1.0;
    }
} 