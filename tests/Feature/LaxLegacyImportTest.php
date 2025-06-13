<?php

declare(strict_types=1);

use App\Services\MapImportService;
use App\Models\TileMap;
use App\Models\TileSet;
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