<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LayerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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
    ];

    protected $casts = [
        'type' => LayerType::class,
        'x' => 'integer',
        'y' => 'integer',
        'z' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'data' => 'array',
        'visible' => 'boolean',
        'opacity' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'type' => LayerType::Background->value,
        'x' => 0,
        'y' => 0,
        'z' => 0,
        'visible' => true,
        'opacity' => 1.0,
    ];

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