<?php

use App\Models\TileMap;
use App\Models\TileSet;
use App\Models\Layer;
use App\Services\MapExportService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Output\OutputInterface;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

beforeEach(function () {
    // Create test tileset image
    Storage::disk('public')->makeDirectory('tilesets');
    $imagePath = 'tilesets/test-tileset.png';
    if (!Storage::disk('public')->exists($imagePath)) {
        // Create a simple 128x128 PNG image
        $image = imagecreatetruecolor(128, 128);
        imagepng($image, Storage::disk('public')->path($imagePath));
        imagedestroy($image);
    }

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
        'width' => 10,
        'height' => 10,
        'tile_width' => 32,
        'tile_height' => 32
    ]);

    // Create a test layer
    $layer = Layer::factory()->create([
        'name' => 'Test Layer',
        'z' => 0,
        'tile_map_id' => $map->id
    ]);

    // Add some test tiles to the layer
    $layer->data = [
        new \App\ValueObjects\Tile(
            x: 0,
            y: 0,
            brush: new \App\ValueObjects\Brush(
                tileset: $tileset->uuid,
                tileX: 0,
                tileY: 0
            )
        ),
        new \App\ValueObjects\Tile(
            x: 1,
            y: 1,
            brush: new \App\ValueObjects\Brush(
                tileset: $tileset->uuid,
                tileX: 1,
                tileY: 1
            )
        )
    ];
    $layer->save();

    $this->map = $map;
    $this->tileset = $tileset;
    $this->layer = $layer;
});

test('can export map as json', function () {
    $output = \Mockery::mock(OutputInterface::class);
    $output->shouldReceive('info')->andReturnNull();
    $output->shouldReceive('writeln')->andReturnNull();
    $output->shouldReceive('error')->andReturnNull();
    
    $service = new MapExportService($output);
    
    // Prepare export data
    $exportData = $service->prepareExportData($this->map);
    
    // Generate filename and path
    $filename = $service->generateFilename($this->map, 'json');
    $path = $service->getDefaultExportPath($filename);
    
    // Export the map
    $service->exportAsJson($exportData, $path);
    
    // Assert file exists
    expect(Storage::exists($path))->toBeTrue();
    
    // Read the exported file
    $exportedContent = Storage::get($path);
    $exportedData = json_decode($exportedContent, true);
    
    // Basic assertions
    expect($exportedData)->toHaveKey('export_version');
    expect($exportedData)->toHaveKey('map');
    expect($exportedData)->toHaveKey('tilesets');
    expect($exportedData)->toHaveKey('layers');
    
    // Map assertions
    expect($exportedData['map']['name'])->toBe('Test Map');
    expect($exportedData['map']['width'])->toBe(10);
    expect($exportedData['map']['height'])->toBe(10);
    
    // Tileset assertions
    expect($exportedData['tilesets'])->toHaveCount(1);
    expect($exportedData['tilesets'][0]['name'])->toBe('Test Tileset');
    
    // Layer assertions
    expect($exportedData['layers'])->toHaveCount(1);
    expect($exportedData['layers'][0]['name'])->toBe('Test Layer');
});

test('can export map as tmx', function () {
    $output = \Mockery::mock(OutputInterface::class);
    $output->shouldReceive('info')->andReturnNull();
    $output->shouldReceive('writeln')->andReturnNull();
    $output->shouldReceive('error')->andReturnNull();
    
    $service = new MapExportService($output);
    
    // Prepare export data
    $exportData = $service->prepareExportData($this->map);
    
    // Generate filename and path
    $filename = $service->generateFilename($this->map, 'tmx');
    $path = $service->getDefaultExportPath($filename);
    
    // Export the map
    $service->exportAsTmx($exportData, $path);
    
    // Assert file exists
    expect(Storage::exists($path))->toBeTrue();
    
    // Read the exported file
    $exportedContent = Storage::get($path);
    
    // Basic XML assertions
    expect($exportedContent)->toContain('<?xml version="1.0" encoding="UTF-8"?>');
    expect($exportedContent)->toContain('<map');
    expect($exportedContent)->toContain('version="1.10"');
    expect($exportedContent)->toContain('width="10"');
    expect($exportedContent)->toContain('height="10"');
    expect($exportedContent)->toContain('tilewidth="32"');
    expect($exportedContent)->toContain('tileheight="32"');
    
    // Tileset assertions
    expect($exportedContent)->toContain('<tileset');
    expect($exportedContent)->toContain('name="Test Tileset"');
    
    // Layer assertions
    expect($exportedContent)->toContain('<layer');
    expect($exportedContent)->toContain('name="Test Layer"');
});

afterEach(function () {
    // Clean up exported files
    Storage::deleteDirectory('exports');
    \Mockery::close();
}); 