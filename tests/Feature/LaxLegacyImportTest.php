<?php

declare(strict_types=1);

use App\Services\MapImportService;
use App\Models\TileMap;
use App\Models\TileSet;
use App\Models\FieldType;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Ensure test tileset image is in the expected location for import
    Storage::makeDirectory('tests/static/tilesets');
    $src = base_path('tests/static/tilesets/castle_exterior_mc.png');
    $dest = 'tests/static/tilesets/castle_exterior_mc.png';
    if (!Storage::exists($dest)) {
        Storage::put($dest, file_get_contents($src));
    }
});

test('can import LAX legacy map and reference correct tileset', function () {
    $importService = app(MapImportService::class);
    $jsPath = 'tests/static/maps/dalaran.js';
    $format = 'js';
    $options = [
        'preserve_uuid' => false,
        'overwrite' => true,
        'auto_create_tilesets' => true,
    ];

    $result = $importService->importFromFile($jsPath, $format, null, $options);
    $map = $result['map'];
    $tilesets = array_merge(
        $result['tilesets']['created'] ?? [],
        $result['tilesets']['existing'] ?? []
    );

    // Assert map was imported
    expect($map)->not->toBeNull();
    expect($map)->toBeInstanceOf(TileMap::class);
    expect($map->name)->toBe('Dalaran Zentrum');
    expect($map->width)->toBe(20);
    expect($map->height)->toBe(20);

    // Assert at least one tileset was imported and has the correct name (case-insensitive, allow formatted)
    $tileset = collect($tilesets)->first(function ($ts) {
        $name = is_array($ts) ? ($ts['name'] ?? null) : ($ts->name ?? null);
        return $name && (mb_strtolower($name) === 'castle_exterior_mc' || mb_strtolower($name) === 'castle exterior mc');
    });
    expect($tileset)->not->toBeNull();
    expect(mb_strtolower(is_array($tileset) ? $tileset['name'] : $tileset->name))->toContain('castle');

    // Assert at least one tile references the correct tileset UUID
    $layer = $map->layers->first();
    expect($layer)->not->toBeNull();
    expect($layer->data)->not->toBeEmpty();
    $tile = $layer->data[0];
    expect($tile->brush->tileset)->toBe(is_array($tileset) ? ($tileset['uuid'] ?? null) : $tileset->uuid);
});

test('can import LAX legacy map with field type file', function () {
    $importService = app(MapImportService::class);
    $jsPath = 'tests/static/maps/dalaran.js';
    $format = 'js';
    $options = [
        'preserve_uuid' => false,
        'overwrite' => true,
        'auto_create_tilesets' => true,
    ];

    $result = $importService->importFromFile($jsPath, $format, null, $options);
    $map = $result['map'];

    // Assert map was imported
    expect($map)->not->toBeNull();
    expect($map)->toBeInstanceOf(TileMap::class);
    expect($map->name)->toBe('Dalaran Zentrum');

    // Assert field type layer was created
    $fieldTypeLayer = $map->layers->where('type', 'field_type')->first();
    expect($fieldTypeLayer)->not->toBeNull();
    expect($fieldTypeLayer->name)->toBe('Field Types');
    expect($fieldTypeLayer->data)->not->toBeEmpty();

    // Assert field type data was converted correctly
    // The mapping should be: 1→3, 2→1, 3→2
    $fieldTypeData = $fieldTypeLayer->data;
    
    // Check that we have field type data with the correct structure
    $firstFieldType = $fieldTypeData[0];
    expect($firstFieldType)->toHaveKey('x');
    expect($firstFieldType)->toHaveKey('y');
    expect($firstFieldType)->toHaveKey('fieldType');
    
    // Verify the value mapping is correct
    // From dalaran_ft.js, we know there are values 1 and 3 (but not 2)
    $fieldTypeValues = collect($fieldTypeData)->pluck('fieldType')->unique()->values();
    expect($fieldTypeValues)->toContain(2); // old value 3
    expect($fieldTypeValues)->toContain(3); // old value 1
    // Do not check for 1 (old value 2) since it does not exist in dalaran_ft.js
    
    // Verify specific mappings from the dalaran_ft.js file
    // Row 0: [3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3] (all 3s)
    // Row 1: [3,3,3,3,3,3,3,3,3,3,1,1,3,3,3,3,3,3,3,3] (mostly 3s, two 1s)
    // Row 8: [1,1,1,1,1,1,1,1,3,3,1,1,3,3,1,1,1,1,1,1] (mix of 1s and 3s)
    
    // Check that old value 1 (walkable with monsters) maps to field type 3
    $walkableWithMonsters = collect($fieldTypeData)->where('fieldType', 3);
    expect($walkableWithMonsters)->not->toBeEmpty();
    
    // Check that old value 3 (not walkable) maps to field type 2
    $notWalkable = collect($fieldTypeData)->where('fieldType', 2);
    expect($notWalkable)->not->toBeEmpty();
    
    // Verify the layer is positioned correctly (z-index 10)
    expect($fieldTypeLayer->z)->toBe(10);
});

test('import matches existing tileset by name for LAX legacy', function () {
    // Create a tileset with the same name as in the legacy map
    $existingTileset = TileSet::create([
        'name' => 'castle_exterior_mc',
        'image_path' => 'tilesets/castle_exterior_mc.png',
        'image_width' => 256,
        'image_height' => 6176,
        'tile_width' => 32,
        'tile_height' => 32,
        'tile_count' => 1544,
        'first_gid' => 1,
        'margin' => 0,
        'spacing' => 0,
    ]);

    $importService = app(MapImportService::class);
    $jsPath = 'tests/static/maps/dalaran.js';
    $format = 'js';
    $options = [
        'preserve_uuid' => false,
        'overwrite' => true,
        'auto_create_tilesets' => false, // Should not create a new one
    ];

    $result = $importService->importFromFile($jsPath, $format, null, $options);
    $map = $result['map'];
    $tilesets = array_merge(
        $result['tilesets']['created'] ?? [],
        $result['tilesets']['existing'] ?? []
    );

    // Assert the existing tileset was matched and no new one was created
    $matched = collect($tilesets)->first(function ($ts) {
        $name = is_array($ts) ? ($ts['name'] ?? null) : ($ts->name ?? null);
        return mb_strtolower($name) === 'castle_exterior_mc';
    });
    expect($matched)->not->toBeNull();
    expect(($matched['uuid'] ?? $matched->uuid))->toBe($existingTileset->uuid);
    expect(TileSet::where('name', 'castle_exterior_mc')->count())->toBe(1);

    // Assert at least one tile references the existing tileset's UUID
    $layer = $map->layers->first();
    expect($layer)->not->toBeNull();
    expect($layer->data)->not->toBeEmpty();
    $tile = $layer->data[0];
    expect($tile->brush->tileset)->toBe($existingTileset->uuid);
});

test('import copies missing tileset image to public storage for LAX legacy', function () {
    // Remove the tileset if it exists to simulate missing tileset
    TileSet::query()->where('name', 'castle_exterior_mc')->delete();
    $importService = app(MapImportService::class);
    $jsPath = 'tests/static/maps/dalaran.js';
    $format = 'js';
    $options = [
        'preserve_uuid' => false,
        'overwrite' => true,
        'auto_create_tilesets' => true,
    ];

    $result = $importService->importFromFile($jsPath, $format, null, $options);
    $tilesets = array_merge(
        $result['tilesets']['created'] ?? [],
        $result['tilesets']['existing'] ?? []
    );
    $tileset = collect($tilesets)->first(function ($ts) {
        $name = is_array($ts) ? ($ts['name'] ?? null) : ($ts->name ?? null);
        return mb_strtolower($name) === 'castle_exterior_mc';
    });
    expect($tileset)->not->toBeNull();
    $imagePath = is_array($tileset) ? ($tileset['image_path'] ?? null) : ($tileset->image_path ?? null);
    // Assert the image was copied to public storage and matches the convention
    expect($imagePath)->not->toBeNull();
    expect($imagePath)->toStartWith('tilesets/');
    expect(Storage::disk('public')->exists($imagePath))->toBeTrue();
});

test('imports legacy map with custom tileset directory', function () {
    // Create a custom tileset directory
    $customDir = 'custom_tilesets';
    Storage::disk('public')->makeDirectory($customDir);
    
    // Copy the tileset image to the custom directory
    $sourcePath = base_path('tests/static/tilesets/castle_exterior_mc.png');
    $targetPath = "{$customDir}/castle_exterior_mc.png";
    Storage::disk('public')->put($targetPath, file_get_contents($sourcePath));

    // Import the map with custom tileset directory
    $importService = app(MapImportService::class);
    $jsPath = 'tests/static/maps/dalaran.js';
    $format = 'js';
    $options = [
        'preserve_uuid' => false,
        'overwrite' => true,
        'auto_create_tilesets' => true,
        'tileset_directory' => $customDir,
    ];

    $result = $importService->importFromFile($jsPath, $format, null, $options);
    $map = $result['map'];
    $tilesets = array_merge(
        $result['tilesets']['created'] ?? [],
        $result['tilesets']['existing'] ?? []
    );

    // Verify the map was imported
    expect($map)->not->toBeNull();
    expect($map)->toBeInstanceOf(TileMap::class);
    expect($map->name)->toBe('Dalaran Zentrum');
    expect($map->width)->toBe(20);
    expect($map->height)->toBe(20);

    // Verify the tileset was created with correct path (always 'tilesets/castle_exterior_mc.png')
    $tileset = collect($tilesets)->first(function ($ts) {
        $name = is_array($ts) ? ($ts['name'] ?? null) : ($ts->name ?? null);
        return $name && (mb_strtolower($name) === 'castle_exterior_mc' || mb_strtolower($name) === 'castle exterior mc');
    });
    expect($tileset)->not->toBeNull();
    expect(mb_strtolower(is_array($tileset) ? $tileset['name'] : $tileset->name))->toContain('castle');
    expect((is_array($tileset) ? $tileset['image_path'] : $tileset->image_path))->toBe('tilesets/castle_exterior_mc.png');

    // Verify the image was copied to the correct storage location
    expect(Storage::disk('public')->exists('tilesets/castle_exterior_mc.png'))->toBeTrue();

    // Clean up
    Storage::disk('public')->deleteDirectory($customDir);
    Storage::disk('public')->delete('tilesets/castle_exterior_mc.png');
});

afterEach(function () {
    // Clean up imported maps and tilesets
    TileMap::query()->where('name', 'Dalaran Zentrum')->delete();
    TileSet::query()->where('name', 'castle_exterior_mc')->delete();
    Storage::delete('tests/static/tilesets/castle_exterior_mc.png');
}); 