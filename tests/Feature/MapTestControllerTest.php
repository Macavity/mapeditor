<?php

use App\Models\TileMap;
use App\Models\Layer;
use App\Models\TileSet;
use App\Models\User;
use App\ValueObjects\Tile;
use App\ValueObjects\Brush;
use Illuminate\Foundation\Testing\RefreshDatabase;

beforeEach(function () {
    // Create and authenticate a user
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Create a test tileset
    $this->tileset = TileSet::factory()->create([
        'name' => 'Test Tileset',
        'tile_width' => 32,
        'tile_height' => 32,
    ]);

    // Create a test map
    $this->map = TileMap::factory()->create([
        'name' => 'Test Map',
        'width' => 5,
        'height' => 5,
        'tile_width' => 32,
        'tile_height' => 32
    ]);
});

test('shows map test page with layers', function () {
    // Create layers with different types
    $backgroundLayer = Layer::factory()->create([
        'name' => 'Background',
        'type' => 'background',
        'z' => 0,
        'tile_map_id' => $this->map->id,
        'visible' => true,
    ]);

    $floorLayer = Layer::factory()->create([
        'name' => 'Floor',
        'type' => 'floor',
        'z' => 1,
        'tile_map_id' => $this->map->id,
        'visible' => true,
    ]);

    $skyLayer = Layer::factory()->create([
        'name' => 'Sky',
        'type' => 'sky',
        'z' => 4,
        'tile_map_id' => $this->map->id,
        'visible' => true,
    ]);

    $response = $this->get("/maps/{$this->map->uuid}/test");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->component('MapTest')
            ->has('map')
            ->has('layers')
    );

    $responseData = $response->viewData('page')['props'];
    
    // Check that we have the map data
    expect($responseData['map']['uuid'])->toBe($this->map->uuid);
    expect($responseData['map']['name'])->toBe('Test Map');
    
    // Check that we have layers including the player layer
    expect($responseData['layers'])->toHaveCount(4); // 3 original + 1 player
    
    // Check layer ordering
    $layers = $responseData['layers'];
    expect($layers[0]['type'])->toBe('background');
    expect($layers[0]['z'])->toBe(0);
    expect($layers[1]['type'])->toBe('floor');
    expect($layers[1]['z'])->toBe(1);
    expect($layers[2]['type'])->toBe('object');
    expect($layers[2]['z'])->toBe(2); // Should be inserted after floor
    expect($layers[3]['type'])->toBe('sky');
    expect($layers[3]['z'])->toBe(5); // Should be increased by 1
});

test('handles map with only floor layers', function () {
    $floorLayer1 = Layer::factory()->create([
        'name' => 'Floor 1',
        'type' => 'floor',
        'z' => 1,
        'tile_map_id' => $this->map->id,
        'visible' => true,
    ]);

    $floorLayer2 = Layer::factory()->create([
        'name' => 'Floor 2',
        'type' => 'floor',
        'z' => 3,
        'tile_map_id' => $this->map->id,
        'visible' => true,
    ]);

    $response = $this->get("/maps/{$this->map->uuid}/test");

    $response->assertStatus(200);
    
    $responseData = $response->viewData('page')['props'];
    $layers = $responseData['layers'];
    
    // Should have 3 layers: 2 floor + 1 player
    expect($layers)->toHaveCount(3);
    
    // Player should be inserted after the highest floor layer (z=3)
    expect($layers[2]['type'])->toBe('object');
    expect($layers[2]['z'])->toBe(4);
});

test('handles map with only sky layers', function () {
    $skyLayer1 = Layer::factory()->create([
        'name' => 'Sky 1',
        'type' => 'sky',
        'z' => 4,
        'tile_map_id' => $this->map->id,
        'visible' => true,
    ]);

    $skyLayer2 = Layer::factory()->create([
        'name' => 'Sky 2',
        'type' => 'sky',
        'z' => 6,
        'tile_map_id' => $this->map->id,
        'visible' => true,
    ]);

    $response = $this->get("/maps/{$this->map->uuid}/test");

    $response->assertStatus(200);
    
    $responseData = $response->viewData('page')['props'];
    $layers = $responseData['layers'];
    
    // Should have 3 layers: 2 sky + 1 player
    expect($layers)->toHaveCount(3);
    
    // Player should be inserted before the lowest sky layer
    expect($layers[0]['type'])->toBe('object');
    expect($layers[0]['z'])->toBe(4);
    
    // Sky layers should be increased by 1
    expect($layers[1]['type'])->toBe('sky');
    expect($layers[1]['z'])->toBe(5);
    expect($layers[2]['type'])->toBe('sky');
    expect($layers[2]['z'])->toBe(6);
});

test('handles map with no floor or sky layers', function () {
    $backgroundLayer = Layer::factory()->create([
        'name' => 'Background',
        'type' => 'background',
        'z' => 0,
        'tile_map_id' => $this->map->id,
        'visible' => true,
    ]);

    $fieldLayer = Layer::factory()->create([
        'name' => 'Field',
        'type' => 'field_type',
        'z' => 2,
        'tile_map_id' => $this->map->id,
        'visible' => true,
    ]);

    $response = $this->get("/maps/{$this->map->uuid}/test");

    $response->assertStatus(200);
    
    $responseData = $response->viewData('page')['props'];
    $layers = $responseData['layers'];
    
    // Should have 3 layers: 2 original + 1 player
    expect($layers)->toHaveCount(3);
    
    // Player should be inserted at z=0
    expect($layers[0]['type'])->toBe('object');
    expect($layers[0]['z'])->toBe(0);
});

test('excludes hidden layers', function () {
    $visibleLayer = Layer::factory()->create([
        'name' => 'Visible',
        'type' => 'floor',
        'z' => 1,
        'tile_map_id' => $this->map->id,
        'visible' => true,
    ]);

    $hiddenLayer = Layer::factory()->create([
        'name' => 'Hidden',
        'type' => 'floor',
        'z' => 2,
        'tile_map_id' => $this->map->id,
        'visible' => false,
    ]);

    $response = $this->get("/maps/{$this->map->uuid}/test");

    $response->assertStatus(200);
    
    $responseData = $response->viewData('page')['props'];
    $layers = $responseData['layers'];
    
    // Should only have 2 layers: 1 visible + 1 player
    expect($layers)->toHaveCount(2);
    
    // Should not include hidden layer
    $layerNames = array_column($layers, 'name');
    expect($layerNames)->not->toContain('Hidden');
    expect($layerNames)->toContain('Visible');
});

test('returns 404 for non-existent map', function () {
    $response = $this->get('/maps/non-existent-uuid/test');
    
    $response->assertStatus(404);
}); 