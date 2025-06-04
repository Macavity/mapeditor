<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    protected $appends = ['image_url'];

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
} 