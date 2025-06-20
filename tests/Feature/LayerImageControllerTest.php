<?php

use App\Models\TileMap;
use App\Models\Layer;
use App\Models\TileSet;
use App\Models\User;
use App\ValueObjects\Tile;
use App\ValueObjects\Brush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Create and authenticate a user
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Create a test tileset
    $this->tileset = TileSet::factory()->create([
        'name' => 'Test Tileset',
        'tile_width' => 32,
        'tile_height' => 32,
        'image_path' => 'tilesets/test-tileset.png'
    ]);

    // Create a test tileset image
    Storage::disk('public')->makeDirectory('tilesets');
    $imagePath = 'tilesets/test-tileset.png';
    
    if (!Storage::disk('public')->exists($imagePath)) {
        $image = imagecreatetruecolor(128, 128);
        $red = imagecolorallocate($image, 255, 0, 0);
        imagefill($image, 0, 0, $red);
        imagepng($image, Storage::disk('public')->path($imagePath));
        imagedestroy($image);
    }

    // Create a test map
    $this->map = TileMap::factory()->create([
        'name' => 'Test Map',
        'width' => 5,
        'height' => 5,
        'tile_width' => 32,
        'tile_height' => 32
    ]);

    // Create a test layer
    $this->layer = Layer::factory()->create([
        'name' => 'Test Layer',
        'type' => 'floor',
        'tile_map_id' => $this->map->id,
        'visible' => true,
        'data' => [
            new Tile(
                x: 0,
                y: 0,
                brush: new Brush(
                    tileset: $this->tileset->uuid,
                    tileX: 0,
                    tileY: 0
                )
            )
        ]
    ]);
});

test('serves existing layer image', function () {
    // Create a mock image file (real PNG)
    $imagePath = "maps/{$this->map->id}/layer_{$this->layer->id}.png";
    
    // Ensure directory exists
    Storage::disk('public')->makeDirectory(dirname($imagePath));
    
    $img = imagecreatetruecolor(32, 32);
    $red = imagecolorallocate($img, 255, 0, 0);
    imagefill($img, 0, 0, $red);
    imagepng($img, Storage::disk('public')->path($imagePath));
    imagedestroy($img);
    // Update layer with image path
    $this->layer->update(['image_path' => $imagePath]);
    $response = $this->get("/layers/{$this->layer->uuid}.png");
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/png');
});

test('generates layer image when it does not exist', function () {
    // Ensure layer has no image path
    $this->layer->update(['image_path' => null]);

    $response = $this->get("/layers/{$this->layer->uuid}.png");

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/png');
    
    // Check that image was generated
    $this->layer->refresh();
    expect($this->layer->image_path)->not->toBeNull();
    expect(Storage::disk('public')->exists($this->layer->image_path))->toBeTrue();
});

test('forces refresh when refresh parameter is provided', function () {
    // Create an old image
    $oldImagePath = "maps/{$this->map->id}/layer_{$this->layer->id}.png";
    Storage::disk('public')->put($oldImagePath, 'old image data');
    $this->layer->update(['image_path' => $oldImagePath]);
    
    // Get the old modification time
    $oldModTime = filemtime(Storage::disk('public')->path($oldImagePath));
    
    // Request with refresh parameter
    $response = $this->get("/layers/{$this->layer->uuid}.png?refresh=true");
    
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/png');
    
    // Check that a new image was generated
    $this->layer->refresh();
    $newModTime = filemtime(Storage::disk('public')->path($this->layer->image_path));
    expect($newModTime)->toBeGreaterThan($oldModTime);
});

test('returns 404 for non-existent layer', function () {
    $response = $this->get('/layers/non-existent-uuid.png');
    
    $response->assertStatus(404);
});

test('returns 200 and generates image when tileset is missing', function () {
    // Create a layer with invalid tileset reference
    $invalidLayer = Layer::factory()->create([
        'name' => 'Invalid Layer',
        'type' => 'floor',
        'tile_map_id' => $this->map->id,
        'visible' => true,
        'data' => [
            new Tile(
                x: 0,
                y: 0,
                brush: new Brush(
                    tileset: 'non-existent-uuid',
                    tileX: 0,
                    tileY: 0
                )
            )
        ]
    ]);

    $response = $this->get("/layers/{$invalidLayer->uuid}.png");
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/png');
    $invalidLayer->refresh();
    expect($invalidLayer->image_path)->not->toBeNull();
    expect(Storage::disk('public')->exists($invalidLayer->image_path))->toBeTrue();
});

test('handles layer with no tile data', function () {
    // Create a layer with no data
    $emptyLayer = Layer::factory()->create([
        'name' => 'Empty Layer',
        'type' => 'background',
        'tile_map_id' => $this->map->id,
        'visible' => true,
        'data' => []
    ]);

    $response = $this->get("/layers/{$emptyLayer->uuid}.png");

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/png');
    
    // Check that an empty image was generated
    $emptyLayer->refresh();
    expect($emptyLayer->image_path)->not->toBeNull();
    expect(Storage::disk('public')->exists($emptyLayer->image_path))->toBeTrue();
});

test('serves image without authentication', function () {
    // Create a mock image file (real PNG)
    $imagePath = "maps/{$this->map->id}/layer_{$this->layer->id}.png";
    
    // Ensure directory exists
    Storage::disk('public')->makeDirectory(dirname($imagePath));
    
    $img = imagecreatetruecolor(32, 32);
    $red = imagecolorallocate($img, 255, 0, 0);
    imagefill($img, 0, 0, $red);
    imagepng($img, Storage::disk('public')->path($imagePath));
    imagedestroy($img);
    $this->layer->update(['image_path' => $imagePath]);
    // Log out and try to access the image
    auth()->logout();
    $response = $this->get("/layers/{$this->layer->uuid}.png");
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/png');
});

test('handles multiple tilesets in layer', function () {
    // Create a second tileset
    $secondTileset = TileSet::factory()->create([
        'name' => 'Second Tileset',
        'tile_width' => 32,
        'tile_height' => 32,
        'image_path' => 'tilesets/second-tileset.png'
    ]);

    // Create second tileset image
    $secondImagePath = 'tilesets/second-tileset.png';
    $image = imagecreatetruecolor(128, 128);
    $blue = imagecolorallocate($image, 0, 0, 255);
    imagefill($image, 0, 0, $blue);
    imagepng($image, Storage::disk('public')->path($secondImagePath));
    imagedestroy($image);

    // Create layer with tiles from both tilesets
    $multiTilesetLayer = Layer::factory()->create([
        'name' => 'Multi Tileset Layer',
        'type' => 'floor',
        'tile_map_id' => $this->map->id,
        'visible' => true,
        'data' => [
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
                    tileset: $secondTileset->uuid,
                    tileX: 1,
                    tileY: 1
                )
            )
        ]
    ]);

    $response = $this->get("/layers/{$multiTilesetLayer->uuid}.png");

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'image/png');
    
    // Check that image was generated
    $multiTilesetLayer->refresh();
    expect($multiTilesetLayer->image_path)->not->toBeNull();
    expect(Storage::disk('public')->exists($multiTilesetLayer->image_path))->toBeTrue();
});

test('generates only requested layer without affecting others', function () {
    // Create a second layer
    $secondLayer = Layer::factory()->create([
        'name' => 'Second Layer',
        'type' => 'floor',
        'tile_map_id' => $this->map->id,
        'visible' => true,
        'data' => [
            new Tile(
                x: 1,
                y: 1,
                brush: new Brush(
                    tileset: $this->tileset->uuid,
                    tileX: 1,
                    tileY: 1
                )
            )
        ]
    ]);

    // Request the first layer image
    $response = $this->get("/layers/{$this->layer->uuid}.png");
    $response->assertStatus(200);
    
    // Check that only the first layer has an image_path
    $this->layer->refresh();
    $secondLayer->refresh();
    
    expect($this->layer->image_path)->not->toBeNull();
    expect($secondLayer->image_path)->toBeNull();
    
    // Request the second layer image
    $response = $this->get("/layers/{$secondLayer->uuid}.png");
    $response->assertStatus(200);
    
    // Now both should have image_path
    $this->layer->refresh();
    $secondLayer->refresh();
    
    expect($this->layer->image_path)->not->toBeNull();
    expect($secondLayer->image_path)->not->toBeNull();
});

afterEach(function () {
    // Clean up generated files
    Storage::disk('public')->deleteDirectory('maps');
}); 