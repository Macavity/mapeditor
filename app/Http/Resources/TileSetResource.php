<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TileSetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'imageUrl' => $this->image_url,
            'imageWidth' => $this->image_width,
            'imageHeight' => $this->image_height,
            'tileWidth' => $this->tile_width,
            'tileHeight' => $this->tile_height,
            'tileCount' => $this->tile_count,
            'firstGid' => $this->first_gid,
            'margin' => $this->margin,
            'spacing' => $this->spacing,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
} 