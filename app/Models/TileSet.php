<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TileSet',
    type: 'object',
    properties: [
        'id' => new OA\Property(property: 'id', type: 'integer', example: 1),
        'uuid' => new OA\Property(property: 'uuid', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
        'name' => new OA\Property(property: 'name', type: 'string', example: 'My Tile Set'),
        'image_width' => new OA\Property(property: 'image_width', type: 'integer', example: 512),
        'image_height' => new OA\Property(property: 'image_height', type: 'integer', example: 512),
        'tile_width' => new OA\Property(property: 'tile_width', type: 'integer', example: 32),
        'tile_height' => new OA\Property(property: 'tile_height', type: 'integer', example: 32),
        'tile_count' => new OA\Property(property: 'tile_count', type: 'integer', example: 256),
        'first_gid' => new OA\Property(property: 'first_gid', type: 'integer', example: 1),
        'margin' => new OA\Property(property: 'margin', type: 'integer', example: 0),
        'spacing' => new OA\Property(property: 'spacing', type: 'integer', example: 0),
        'created_at' => new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        'updated_at' => new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'TileSetStoreRequest',
    type: 'object',
    required: ['name', 'imageWidth', 'imageHeight', 'tileWidth', 'tileHeight', 'tileCount', 'firstGid'],
    properties: [
        'name' => new OA\Property(property: 'name', type: 'string', example: 'My New Tile Set'),
        'imageWidth' => new OA\Property(property: 'imageWidth', type: 'integer', example: 512, minimum: 1),
        'imageHeight' => new OA\Property(property: 'imageHeight', type: 'integer', example: 512, minimum: 1),
        'tileWidth' => new OA\Property(property: 'tileWidth', type: 'integer', example: 32, minimum: 1),
        'tileHeight' => new OA\Property(property: 'tileHeight', type: 'integer', example: 32, minimum: 1),
        'tileCount' => new OA\Property(property: 'tileCount', type: 'integer', example: 256, minimum: 1),
        'firstGid' => new OA\Property(property: 'firstGid', type: 'integer', example: 1, minimum: 1),
        'margin' => new OA\Property(property: 'margin', type: 'integer', example: 0, minimum: 0),
        'spacing' => new OA\Property(property: 'spacing', type: 'integer', example: 0, minimum: 0),
    ]
)]
#[OA\Schema(
    schema: 'TileSetUpdateRequest',
    type: 'object',
    properties: [
        'name' => new OA\Property(property: 'name', type: 'string', example: 'Updated Tile Set Name'),
        'imageWidth' => new OA\Property(property: 'imageWidth', type: 'integer', example: 1024, minimum: 1),
        'imageHeight' => new OA\Property(property: 'imageHeight', type: 'integer', example: 1024, minimum: 1),
        'tileWidth' => new OA\Property(property: 'tileWidth', type: 'integer', example: 64, minimum: 1),
        'tileHeight' => new OA\Property(property: 'tileHeight', type: 'integer', example: 64, minimum: 1),
        'tileCount' => new OA\Property(property: 'tileCount', type: 'integer', example: 256, minimum: 1),
        'firstGid' => new OA\Property(property: 'firstGid', type: 'integer', example: 1, minimum: 1),
        'margin' => new OA\Property(property: 'margin', type: 'integer', example: 2, minimum: 0),
        'spacing' => new OA\Property(property: 'spacing', type: 'integer', example: 2, minimum: 0),
    ]
)]
class TileSet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'image_path',
        'image_width',
        'image_height',
        'tile_width',
        'tile_height',
        'tile_count',
        'first_gid',
        'margin',
        'spacing',
    ];

    protected $casts = [
        'image_width' => 'integer',
        'image_height' => 'integer',
        'tile_width' => 'integer',
        'tile_height' => 'integer',
        'tile_count' => 'integer',
        'first_gid' => 'integer',
        'margin' => 'integer',
        'spacing' => 'integer',
    ];

    protected $appends = ['image_url', 'tiles_per_row'];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($tileSet) {
            $tileSet->uuid = $tileSet->uuid ?? (string) \Illuminate\Support\Str::uuid();
        });

        static::deleting(function ($tileSet) {
            if ($tileSet->image_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($tileSet->image_path);
            }
        });
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->image_path) : null;
    }

    public function getTilesPerRowAttribute(): int
    {
        if ($this->tile_width > 0) {
            return (int) ($this->image_width / $this->tile_width);
        }
        return 0;
    }
} 