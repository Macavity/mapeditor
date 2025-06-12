<?php

declare(strict_types=1);

use App\Services\MapImportService;
use App\Models\TileMap;
use App\Models\TileSet;
use Illuminate\Support\Facades\Storage;
use App\ValueObjects\Tile;
use App\ValueObjects\Brush;

beforeEach(function () {
    // Ensure test tileset image is in the expected location for import
    Storage::makeDirectory('tests/static/tilesets');
    $src = base_path('tests/static/tilesets/castle_exterior_mc.png');
    $dest = 'tests/static/tilesets/castle_exterior_mc.png';
    if (!Storage::exists($dest)) {
        Storage::put($dest, file_get_contents($src));
    }
});

test('can import TMX map with referenced tileset', function () {
    $importService = app(MapImportService::class);
    $tmxPath = 'tests/static/maps/field.tmx';
    $format = 'tmx';
    $options = [
        'preserve_uuid' => false,
        'overwrite' => true,
        'auto_create_tilesets' => true,
    ];

    $result = $importService->importFromFile($tmxPath, $format, null, $options);
    $map = $result['map'];
    $tilesets = $result['tilesets']['created'] ?? [];

    // Assert map was imported
    expect($map)->not->toBeNull();
    expect($map)->toBeInstanceOf(TileMap::class);
    expect($map->name)->toBe('Imported TMX Map');
    expect($map->width)->toBe(20);
    expect($map->height)->toBe(20);

    // Assert tileset was imported
    expect($tilesets)->not->toBeEmpty();
    $tileset = $tilesets[0];
    expect($tileset)->toBeInstanceOf(TileSet::class);
    expect($tileset->name)->toBe('castle_exterior_mc');
    expect($tileset->image_path)->toContain('castle_exterior_mc.png');

    // Assert at least one layer and tile reference the correct tileset UUID
    $layer = $map->layers->first();
    expect($layer)->not->toBeNull();
    expect($layer->data)->not->toBeEmpty();
    $tile = $layer->data[0];
    expect($tile->brush->tileset)->toBe($tileset->uuid);
    expect($tile->brush->tileX)->toBeInt();
    expect($tile->brush->tileY)->toBeInt();
});

test('import copies tileset image to public storage', function () {
    $importService = app(MapImportService::class);
    $tmxPath = 'tests/static/maps/field.tmx';
    $format = 'tmx';
    $options = [
        'preserve_uuid' => false,
        'overwrite' => true,
        'auto_create_tilesets' => true,
    ];

    $result = $importService->importFromFile($tmxPath, $format, null, $options);
    $tilesets = $result['tilesets']['created'] ?? [];
    expect($tilesets)->not->toBeEmpty();
    $tileset = $tilesets[0];
    // Assert the image was copied to public storage
    expect(Storage::disk('public')->exists($tileset->image_path))->toBeTrue();
});

test('import matches existing tileset by name', function () {
    // Create a tileset with the same name as in the TMX file
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
    $tmxPath = 'tests/static/maps/field.tmx';
    $format = 'tmx';
    $options = [
        'preserve_uuid' => false,
        'overwrite' => true,
        'auto_create_tilesets' => false, // Should not create a new one
    ];

    $result = $importService->importFromFile($tmxPath, $format, null, $options);
    $map = $result['map'];
    $tilesets = $result['tilesets']['existing'] ?? [];

    // Assert the existing tileset was matched and no new one was created
    expect($tilesets)->not->toBeEmpty();
    expect($tilesets[0]['name'])->toBe('castle_exterior_mc');
    expect(TileSet::where('name', 'castle_exterior_mc')->count())->toBe(1);

    // Assert at least one tile references the existing tileset's UUID
    $layer = $map->layers->first();
    expect($layer)->not->toBeNull();
    expect($layer->data)->not->toBeEmpty();
    $tile = $layer->data[0];
    expect($tile->brush->tileset)->toBe($existingTileset->uuid);
});

test('import matches existing tileset by uuid', function () {
    $uuid = (string) \Illuminate\Support\Str::uuid();
    $existingTileset = TileSet::create([
        'uuid' => $uuid,
        'name' => 'castle_exterior_mc',
        'image_path' => 'tilesets/other.png',
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
    $tmxPath = 'tests/static/maps/field.tmx';
    $format = 'tmx';
    $options = [
        'preserve_uuid' => false,
        'overwrite' => true,
        'auto_create_tilesets' => false,
    ];

    $result = $importService->importFromFile($tmxPath, $format, null, $options);
    $map = $result['map'];
    $layer = $map->layers->first();
    $tile = $layer->data[0];
    expect($tile->brush->tileset)->toBe($uuid);
});

test('import matches existing tileset by name case-insensitive', function () {
    $existingTileset = TileSet::create([
        'name' => 'Castle_Exterior_Mc', // different case
        'image_path' => 'tilesets/other.png',
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
    $tmxPath = 'tests/static/maps/field.tmx';
    $format = 'tmx';
    $options = [
        'preserve_uuid' => false,
        'overwrite' => true,
        'auto_create_tilesets' => false,
    ];

    $result = $importService->importFromFile($tmxPath, $format, null, $options);
    $map = $result['map'];
    $layer = $map->layers->first();
    $tile = $layer->data[0];
    expect($tile->brush->tileset)->toBe($existingTileset->uuid);
});

test('import matches existing tileset by image filename', function () {
    $existingTileset = TileSet::create([
        'name' => 'other_name',
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
    $tmxPath = 'tests/static/maps/field.tmx';
    $format = 'tmx';
    $options = [
        'preserve_uuid' => false,
        'overwrite' => true,
        'auto_create_tilesets' => false,
    ];

    $result = $importService->importFromFile($tmxPath, $format, null, $options);
    $map = $result['map'];
    $layer = $map->layers->first();
    $tile = $layer->data[0];
    expect($tile->brush->tileset)->toBe($existingTileset->uuid);
});

afterEach(function () {
    // Clean up imported maps and tilesets
    TileMap::query()->where('name', 'Imported TMX Map')->delete();
    TileSet::query()->where('name', 'castle_exterior_mc')->delete();
    Storage::delete('tests/static/tilesets/castle_exterior_mc.png');
}); 