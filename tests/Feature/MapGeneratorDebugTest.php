<?php

use App\Models\TileMap;
use App\Models\TileSet;
use App\Models\Layer;
use App\Services\MapGenerator;
use App\ValueObjects\Tile;
use App\ValueObjects\Brush;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

test('debug map generator', function () {
    // Create test tileset image
    Storage::disk('public')->makeDirectory('tilesets');
    $imagePath = 'tilesets/test-tileset.png';
    
    // Create a simple 128x128 PNG image
    $image = imagecreatetruecolor(128, 128);
    $red = imagecolorallocate($image, 255, 0, 0);
    imagefill($image, 0, 0, $red);
    imagepng($image, Storage::disk('public')->path($imagePath));
    imagedestroy($image);

    // Create a test tileset
    $tileset = TileSet::factory()->create([
        'name' => 'Test Tileset',
        'tile_width' => 32,
        'tile_height' => 32,
        'image_width' => 128,
        'image_height' => 128,
        'tile_count' => 16,
        'image_path' => $imagePath
    ]);

    // Create a test map
    $map = TileMap::factory()->create([
        'name' => 'Test Map',
        'width' => 2,
        'height' => 2,
        'tile_width' => 32,
        'tile_height' => 32
    ]);

    // Create visible layer
    $layer = Layer::factory()->create([
        'name' => 'Visible Layer',
        'tile_map_id' => $map->id,
        'visible' => true
    ]);

    // Add test tiles to the layer
    $layer->data = [
        new Tile(
            x: 0,
            y: 0,
            brush: new Brush(
                tileset: $tileset->uuid,
                tileX: 0,
                tileY: 0
            )
        )
    ];
    $layer->save();

    // Don't mock Log to see what's happening
    $mapGenerator = new MapGenerator();
    
    try {
        $mapGenerator->generateMapImage($map);
        
        // Check what happened
        $layerPath = "maps/{$map->id}/layer_{$layer->id}.png";
        dump("Layer path: " . $layerPath);
        dump("Storage exists: " . (Storage::disk('public')->exists($layerPath) ? 'true' : 'false'));
        dump("Full path: " . Storage::disk('public')->path($layerPath));
        dump("File exists: " . (file_exists(Storage::disk('public')->path($layerPath)) ? 'true' : 'false'));
        
        $layer->refresh();
        dump("Layer image_path: " . $layer->image_path);
        
        // Check if directory was created
        $baseDirectory = "maps/{$map->id}";
        dump("Base directory exists: " . (Storage::disk('public')->exists($baseDirectory) ? 'true' : 'false'));
        
    } catch (Exception $e) {
        dump("Exception: " . $e->getMessage());
        dump("Stack trace: " . $e->getTraceAsString());
    }
    
    // Clean up
    Storage::disk('public')->deleteDirectory('maps');
}); 