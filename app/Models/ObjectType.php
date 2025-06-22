<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObjectType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'color',
        'description',
        'is_solid',
    ];

    protected $casts = [
        'is_solid' => 'boolean',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Get the color as a CSS-compatible value
     */
    public function getColorAttribute($value): string
    {
        return $value ?: '#000000'; // Default to black if no color
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\ObjectTypeFactory::new();
    }
} 