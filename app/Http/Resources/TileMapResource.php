<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\TileSet;
use App\ValueObjects\Tile;
use Illuminate\Http\Request;

class TileMapResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'width' => $this->width,
            'height' => $this->height,
            'tile_width' => $this->tile_width,
            'tile_height' => $this->tile_height,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'layers' => LayerResource::collection($this->whenLoaded('layers')),
            'tileset_usage' => $this->getTilesetUsage(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Aggregate used tiles per tileset across all layers.
     *
     * @return array<string, array<int, array<string, int>>>
     */
    protected function getTilesetUsage(): array
    {
        $usage = [];
        $layers = $this->whenLoaded('layers', fn () => $this->layers, $this->layers ?? []);
        foreach ($layers as $layer) {
            if (!is_array($layer->data) && !($layer->data instanceof \Traversable)) continue;
            foreach ($layer->data as $tile) {
                if (!$tile instanceof Tile) continue;
                $tileset = $tile->brush->tileset;

                $usage[$tileset] = ($usage[$tileset] ?? 0) + 1;
            }
        }
        
        return $usage;
    }
} 