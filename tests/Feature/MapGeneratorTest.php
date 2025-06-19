<?php

use App\Models\TileMap;
use App\Models\TileSet;
use App\Models\Layer;
use App\Services\MapGenerator;
use App\ValueObjects\Tile;
use App\ValueObjects\Brush;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

beforeEach(function () {
    // Create test tileset image
    Storage::disk('public')->makeDirectory('tilesets');
    $imagePath = 'tilesets/test-tileset.png';
    
    if (!Storage::disk('public')->exists($imagePath)) {
        // Create a simple 128x128 PNG image with some content
        $image = imagecreatetruecolor(128, 128);
        $red = imagecolorallocate($image, 255, 0, 0);
        $green = imagecolorallocate($image, 0, 255, 0);
        $blue = imagecolorallocate($image, 0, 0, 255);
        
        // Fill with a pattern
        for ($x = 0; $x < 128; $x += 32) {
            for ($y = 0; $y < 128; $y += 32) {
                $color = ($x + $y) % 96 === 0 ? $red : (($x + $y) % 64 === 0 ? $green : $blue);
                imagefilledrectangle($image, $x, $y, $x + 31, $y + 31, $color);
            }
        }
        
        imagepng($image, Storage::disk('public')->path($imagePath));
        imagedestroy($image);
    }

    // Create a test tileset
    $this->tileset = TileSet::factory()->create([
        'name' => 'Test Tileset',
        'tile_width' => 32,
        'tile_height' => 32,
        'image_width' => 128,
        'image_height' => 128,
        'tile_count' => 16,
        'image_path' => $imagePath
    ]);

    // Create a test map
    $this->map = TileMap::factory()->create([
        'name' => 'Test Map',
        'width' => 5,
        'height' => 5,
        'tile_width' => 32,
        'tile_height' => 32
    ]);

    // Create visible layer
    $this->visibleLayer = Layer::factory()->create([
        'name' => 'Visible Layer',
        'z' => 0,
        'tile_map_id' => $this->map->id,
        'visible' => true
    ]);

    // Create hidden layer
    $this->hiddenLayer = Layer::factory()->create([
        'name' => 'Hidden Layer',
        'z' => 1,
        'tile_map_id' => $this->map->id,
        'visible' => false
    ]);

    // Add test tiles to the visible layer
    $this->visibleLayer->data = [
        new Tile(
            x: 0,
            y: 0,
            brush: new Brush(
                tileset: $this->tileset->uuid,
                tileX: 0,
                tileY: 0
            )
        ),
        new Tile(
            x: 1,
            y: 1,
            brush: new Brush(
                tileset: $this->tileset->uuid,
                tileX: 1,
                tileY: 1
            )
        ),
        new Tile(
            x: 2,
            y: 2,
            brush: new Brush(
                tileset: $this->tileset->uuid,
                tileX: 2,
                tileY: 2
            )
        )
    ];
    $this->visibleLayer->save();

    // Add test tiles to the hidden layer
    $this->hiddenLayer->data = [
        new Tile(
            x: 3,
            y: 3,
            brush: new Brush(
                tileset: $this->tileset->uuid,
                tileX: 3,
                tileY: 3
            )
        )
    ];
    $this->hiddenLayer->save();

    $this->mapGenerator = new MapGenerator();
});

test('generates map images for visible layers only', function () {
    // Mock Log facade to avoid actual logging during tests
    Log::shouldReceive('info')->andReturnNull();
    Log::shouldReceive('debug')->andReturnNull();
    Log::shouldReceive('warning')->andReturnNull();
    Log::shouldReceive('error')->andReturnNull();

    $this->mapGenerator->generateMapImage($this->map);

    // Check that only visible layer image was created
    $visibleLayerPath = "public/maps/{$this->map->id}/layer_{$this->visibleLayer->id}.png";
    $hiddenLayerPath = "public/maps/{$this->map->id}/layer_{$this->hiddenLayer->id}.png";

    expect(Storage::exists($visibleLayerPath))->toBeTrue();
    expect(Storage::exists($hiddenLayerPath))->toBeFalse();

    // Check that layer image_path was updated
    $this->visibleLayer->refresh();
    expect($this->visibleLayer->image_path)->toBe($visibleLayerPath);
});

test('creates correct image dimensions', function () {
    Log::shouldReceive('info')->andReturnNull();
    Log::shouldReceive('debug')->andReturnNull();
    Log::shouldReceive('warning')->andReturnNull();
    Log::shouldReceive('error')->andReturnNull();

    $this->mapGenerator->generateMapImage($this->map);

    $imagePath = Storage::path("app/public/maps/{$this->map->id}/layer_{$this->visibleLayer->id}.png");
    
    expect(file_exists($imagePath))->toBeTrue();
    
    // Check image dimensions
    $imageInfo = getimagesize($imagePath);
    $expectedWidth = $this->map->width * $this->map->tile_width; // 5 * 32 = 160
    $expectedHeight = $this->map->height * $this->map->tile_height; // 5 * 32 = 160
    
    expect($imageInfo[0])->toBe($expectedWidth);
    expect($imageInfo[1])->toBe($expectedHeight);
    expect($imageInfo[2])->toBe(IMAGETYPE_PNG);
});

test('handles map with no visible layers', function () {
    // Create a map with only hidden layers
    $mapWithHiddenLayers = TileMap::factory()->create([
        'name' => 'Hidden Layers Map',
        'width' => 3,
        'height' => 3,
        'tile_width' => 32,
        'tile_height' => 32
    ]);

    $hiddenLayer = Layer::factory()->create([
        'name' => 'Hidden Layer',
        'tile_map_id' => $mapWithHiddenLayers->id,
        'visible' => false
    ]);

    Log::shouldReceive('warning')->with("No visible layers found for map ID: {$mapWithHiddenLayers->id}")->andReturnNull();
    Log::shouldReceive('info')->andReturnNull();
    Log::shouldReceive('debug')->andReturnNull();
    Log::shouldReceive('error')->andReturnNull();

    $this->mapGenerator->generateMapImage($mapWithHiddenLayers);

    // Should not create any image files
    $layerPath = "public/maps/{$mapWithHiddenLayers->id}/layer_{$hiddenLayer->id}.png";
    expect(Storage::exists($layerPath))->toBeFalse();
});

test('handles layer with no tile data', function () {
    // Create a layer with no data
    $emptyLayer = Layer::factory()->create([
        'name' => 'Empty Layer',
        'tile_map_id' => $this->map->id,
        'visible' => true,
        'data' => []
    ]);

    Log::shouldReceive('info')->andReturnNull();
    Log::shouldReceive('debug')->andReturnNull();
    Log::shouldReceive('warning')->andReturnNull();
    Log::shouldReceive('error')->andReturnNull();

    $this->mapGenerator->generateMapImage($this->map);

    // Should create an empty image file
    $layerPath = "public/maps/{$this->map->id}/layer_{$emptyLayer->id}.png";
    expect(Storage::exists($layerPath))->toBeTrue();

    $emptyLayer->refresh();
    expect($emptyLayer->image_path)->toBe($layerPath);
});

test('handles missing tileset gracefully', function () {
    // Create new data array with the additional tile
    $newData = $this->visibleLayer->data;
    $newData[] = new Tile(
        x: 4,
        y: 4,
        brush: new Brush(
            tileset: 'non-existent-uuid',
            tileX: 0,
            tileY: 0
        )
    );
    
    $this->visibleLayer->data = $newData;
    $this->visibleLayer->save();

    Log::shouldReceive('info')->andReturnNull();
    Log::shouldReceive('debug')->andReturnNull();
    Log::shouldReceive('warning')->andReturnNull();
    Log::shouldReceive('error')->andReturnNull();

    $this->mapGenerator->generateMapImage($this->map);

    // Should still create the image file
    $layerPath = "public/maps/{$this->map->id}/layer_{$this->visibleLayer->id}.png";
    expect(Storage::exists($layerPath))->toBeTrue();
});

test('handles missing tileset image gracefully', function () {
    // Create a tileset with non-existent image
    $tilesetWithMissingImage = TileSet::factory()->create([
        'name' => 'Missing Image Tileset',
        'tile_width' => 32,
        'tile_height' => 32,
        'image_path' => 'tilesets/non-existent.png'
    ]);

    // Create new data array with the additional tile
    $newData = $this->visibleLayer->data;
    $newData[] = new Tile(
        x: 4,
        y: 4,
        brush: new Brush(
            tileset: $tilesetWithMissingImage->uuid,
            tileX: 0,
            tileY: 0
        )
    );
    
    $this->visibleLayer->data = $newData;
    $this->visibleLayer->save();

    Log::shouldReceive('info')->andReturnNull();
    Log::shouldReceive('debug')->andReturnNull();
    Log::shouldReceive('warning')->andReturnNull();
    Log::shouldReceive('error')->andReturnNull();

    $this->mapGenerator->generateMapImage($this->map);

    // Should still create the image file
    $layerPath = "public/maps/{$this->map->id}/layer_{$this->visibleLayer->id}.png";
    expect(Storage::exists($layerPath))->toBeTrue();
});

test('creates directory structure correctly', function () {
    Log::shouldReceive('info')->andReturnNull();
    Log::shouldReceive('debug')->andReturnNull();
    Log::shouldReceive('warning')->andReturnNull();
    Log::shouldReceive('error')->andReturnNull();

    $this->mapGenerator->generateMapImage($this->map);

    // Check that the base directory was created
    $baseDirectory = "public/maps/{$this->map->id}";
    expect(Storage::exists($baseDirectory))->toBeTrue();

    // Check that the layer image was created in the correct location
    $layerPath = "{$baseDirectory}/layer_{$this->visibleLayer->id}.png";
    expect(Storage::exists($layerPath))->toBeTrue();
});

test('updates layer image_path after generation', function () {
    Log::shouldReceive('info')->andReturnNull();
    Log::shouldReceive('debug')->andReturnNull();
    Log::shouldReceive('warning')->andReturnNull();
    Log::shouldReceive('error')->andReturnNull();

    // Initially, image_path should be null
    expect($this->visibleLayer->image_path)->toBeNull();

    $this->mapGenerator->generateMapImage($this->map);

    // After generation, image_path should be set
    $this->visibleLayer->refresh();
    expect($this->visibleLayer->image_path)->toBe("public/maps/{$this->map->id}/layer_{$this->visibleLayer->id}.png");
});

test('handles multiple tilesets in same layer', function () {
    // Create a second tileset
    $secondTileset = TileSet::factory()->create([
        'name' => 'Second Tileset',
        'tile_width' => 32,
        'tile_height' => 32,
        'image_width' => 128,
        'image_height' => 128,
        'image_path' => 'tilesets/test-tileset.png' // Reuse the same image for simplicity
    ]);

    // Create new data array with tiles from both tilesets
    $newData = $this->visibleLayer->data;
    $newData[] = new Tile(
        x: 3,
        y: 3,
        brush: new Brush(
            tileset: $secondTileset->uuid,
            tileX: 0,
            tileY: 0
        )
    );
    
    $this->visibleLayer->data = $newData;
    $this->visibleLayer->save();

    Log::shouldReceive('info')->andReturnNull();
    Log::shouldReceive('debug')->andReturnNull();
    Log::shouldReceive('warning')->andReturnNull();
    Log::shouldReceive('error')->andReturnNull();

    $this->mapGenerator->generateMapImage($this->map);

    // Should create the image successfully
    $layerPath = "public/maps/{$this->map->id}/layer_{$this->visibleLayer->id}.png";
    expect(Storage::exists($layerPath))->toBeTrue();
});

afterEach(function () {
    // Clean up generated files
    Storage::deleteDirectory('public/maps');
}); 