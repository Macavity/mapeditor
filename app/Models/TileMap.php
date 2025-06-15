<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TileMap',
    type: 'object',
    properties: [
        'id' => new OA\Property(property: 'id', type: 'integer', example: 1),
        'name' => new OA\Property(property: 'name', type: 'string', example: 'My Tile Map'),
        'width' => new OA\Property(property: 'width', type: 'integer', example: 10),
        'height' => new OA\Property(property: 'height', type: 'integer', example: 10),
        'tile_width' => new OA\Property(property: 'tile_width', type: 'integer', example: 32),
        'tile_height' => new OA\Property(property: 'tile_height', type: 'integer', example: 32),
        'created_at' => new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        'updated_at' => new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        'creator' => new OA\Property(property: 'creator', ref: '#/components/schemas/User'),
        'layers' => new OA\Property(
            property: 'layers',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Layer')
        ),
    ]
)]
#[OA\Schema(
    schema: 'TileMapStoreRequest',
    type: 'object',
    required: ['name', 'width', 'height', 'tile_width', 'tile_height'],
    properties: [
        'name' => new OA\Property(property: 'name', type: 'string', example: 'My New Tile Map'),
        'width' => new OA\Property(property: 'width', type: 'integer', example: 10, minimum: 1),
        'height' => new OA\Property(property: 'height', type: 'integer', example: 10, minimum: 1),
        'tile_width' => new OA\Property(property: 'tile_width', type: 'integer', example: 32, minimum: 1),
        'tile_height' => new OA\Property(property: 'tile_height', type: 'integer', example: 32, minimum: 1),
    ]
)]
#[OA\Schema(
    schema: 'TileMapUpdateRequest',
    type: 'object',
    properties: [
        'name' => new OA\Property(property: 'name', type: 'string', example: 'Updated Tile Map Name'),
        'width' => new OA\Property(property: 'width', type: 'integer', example: 20, minimum: 1),
        'height' => new OA\Property(property: 'height', type: 'integer', example: 20, minimum: 1),
        'tile_width' => new OA\Property(property: 'tile_width', type: 'integer', example: 32, minimum: 1),
        'tile_height' => new OA\Property(property: 'tile_height', type: 'integer', example: 32, minimum: 1),
    ]
)]
class TileMap extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'width',
        'height',
        'name',
        'tile_width',
        'tile_height',
        'external_creator',
    ];

    protected $hidden = [
        'id',
    ];

    protected $casts = [
        'width' => 'integer',
        'height' => 'integer',
        'tile_width' => 'integer',
        'tile_height' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function layers(): HasMany
    {
        return $this->hasMany(Layer::class);
    }
} 