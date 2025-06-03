<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 