<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Export;

use Carbon\Carbon;

readonly class ExportMapFormatV1
{
    public function __construct(
        public string $export_version,
        public ExportedMapInfoV1 $map,
        public array $tilesets, // ExportedTilesetV1[]
        public array $layers,   // ExportedLayerV1[]
    ) {}

    /**
     * Create from array data (for deserialization).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            export_version: $data['export_version'],
            map: ExportedMapInfoV1::fromArray($data['map']),
            tilesets: array_map(
                fn(array $tileset) => ExportedTilesetV1::fromArray($tileset),
                $data['tilesets'] ?? []
            ),
            layers: array_map(
                fn(array $layer) => ExportedLayerV1::fromArray($layer),
                $data['layers'] ?? []
            ),
        );
    }

    /**
     * Convert to array (for serialization).
     */
    public function toArray(): array
    {
        return [
            'export_version' => $this->export_version,
            'map' => $this->map->toArray(),
            'tilesets' => array_map(
                fn(ExportedTilesetV1 $tileset) => $tileset->toArray(),
                $this->tilesets
            ),
            'layers' => array_map(
                fn(ExportedLayerV1 $layer) => $layer->toArray(),
                $this->layers
            ),
        ];
    }

    /**
     * Convert to JSON string.
     */
    public function toJson(int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES): string
    {
        $json = json_encode($this->toArray(), $flags);
        
        if ($json === false) {
            throw new \RuntimeException('Failed to encode export data as JSON: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Create from JSON string.
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON data: ' . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new \InvalidArgumentException('JSON data must decode to an array');
        }

        return self::fromArray($data);
    }

    /**
     * Get the format version.
     */
    public function getVersion(): string
    {
        return $this->export_version;
    }

    /**
     * Check if this is a specific version.
     */
    public function isVersion(string $version): bool
    {
        return $this->export_version === $version;
    }


} 