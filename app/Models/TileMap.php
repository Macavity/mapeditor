<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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