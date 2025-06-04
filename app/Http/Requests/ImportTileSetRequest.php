<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportTileSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'image' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:5120'], // 5MB max
            'tileWidth' => ['required', 'integer', 'min:1'],
            'tileHeight' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.max' => 'The image must not be larger than 5MB.',
            'image.mimes' => 'The image must be a PNG, JPG, or JPEG file.',
            'tileWidth.min' => 'The tile width must be at least 1 pixel.',
            'tileHeight.min' => 'The tile height must be at least 1 pixel.',
        ];
    }
} 