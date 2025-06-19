<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LayerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Casts\TileArrayCast;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Layer',
    type: 'object',
    properties: [
        'id' => new OA\Property(property: 'id', type: 'integer', example: 1),
        'tile_map_id' => new OA\Property(property: 'tile_map_id', type: 'integer', example: 1),
        'name' => new OA\Property(property: 'name', type: 'string', example: 'Ground Layer'),
        'type' => new OA\Property(property: 'type', type: 'string', enum: ['tile', 'object'], example: 'tile'),
        'visible' => new OA\Property(property: 'visible', type: 'boolean', example: true),
        'opacity' => new OA\Property(property: 'opacity', type: 'number', format: 'float', example: 1.0, minimum: 0, maximum: 1),
        'order' => new OA\Property(property: 'order', type: 'integer', example: 0),
        'properties' => new OA\Property(property: 'properties', type: 'object', nullable: true),
        'data' => new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    'x' => new OA\Property(property: 'x', type: 'integer', example: 0),
                    'y' => new OA\Property(property: 'y', type: 'integer', example: 0),
                    'brush' => new OA\Property(
                        property: 'brush',
                        type: 'object',
                        properties: [
                            'tileset' => new OA\Property(property: 'tileset', type: 'string', example: '0b74820e-fe8d-41e9-8a46-4f1650e91242'),
                            'tileX' => new OA\Property(property: 'tileX', type: 'integer', example: 0),
                            'tileY' => new OA\Property(property: 'tileY', type: 'integer', example: 0)
                        ]
                    )
                ]
            ),
            nullable: true
        ),
        'created_at' => new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        'updated_at' => new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'LayerStoreRequest',
    type: 'object',
    required: ['tile_map_id', 'name', 'type'],
    properties: [
        'tile_map_id' => new OA\Property(property: 'tile_map_id', type: 'integer', example: 1),
        'name' => new OA\Property(property: 'name', type: 'string', example: 'New Layer'),
        'type' => new OA\Property(property: 'type', type: 'string', enum: ['tile', 'object'], example: 'tile'),
        'visible' => new OA\Property(property: 'visible', type: 'boolean', example: true),
        'opacity' => new OA\Property(property: 'opacity', type: 'number', format: 'float', example: 1.0, minimum: 0, maximum: 1),
        'order' => new OA\Property(property: 'order', type: 'integer', example: 0),
        'properties' => new OA\Property(property: 'properties', type: 'object', nullable: true),
        'data' => new OA\Property(
            property: 'data', 
            type: 'array', 
            items: new OA\Items(type: 'integer'), 
            nullable: true
        ),
    ]
)]
#[OA\Schema(
    schema: 'LayerUpdateRequest',
    type: 'object',
    properties: [
        'name' => new OA\Property(property: 'name', type: 'string', example: 'Updated Layer Name'),
        'visible' => new OA\Property(property: 'visible', type: 'boolean', example: false),
        'opacity' => new OA\Property(property: 'opacity', type: 'number', format: 'float', example: 0.8, minimum: 0, maximum: 1),
        'order' => new OA\Property(property: 'order', type: 'integer', example: 1),
        'properties' => new OA\Property(property: 'properties', type: 'object', nullable: true),
        'data' => new OA\Property(
            property: 'data', 
            type: 'array', 
            items: new OA\Items(type: 'integer'), 
            nullable: true
        ),
    ]
)]
class Layer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tile_map_id',
        'name',
        'type',
        'x',
        'y',
        'z',
        'width',
        'height',
        'data',
        'visible',
        'opacity',
        'image_path',
    ];

    protected $casts = [
        'type' => LayerType::class,
        'x' => 'integer',
        'y' => 'integer',
        'z' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'data' => TileArrayCast::class,
        'visible' => 'boolean',
        'opacity' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be visible in arrays.
     *
     * @var array<int, string>
     */
    protected $visible = [
        'uuid',
        'name',
        'type',
        'x',
        'y',
        'z',
        'width',
        'height',
        'data', // Array of Tile objects
        'visible',
        'opacity',
        'image_path',
        'created_at',
        'updated_at',
    ];

    protected $attributes = [
        'type' => LayerType::Background->value,
        'x' => 0,
        'y' => 0,
        'z' => 0,
        'visible' => true,
        'opacity' => 1.0,
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

    public function tileMap(): BelongsTo
    {
        return $this->belongsTo(TileMap::class);
    }
} 