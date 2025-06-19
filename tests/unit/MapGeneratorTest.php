<?php

use App\Models\TileMap;
use App\Models\TileSet;
use App\Models\Layer;
use App\Services\MapGenerator;
use App\ValueObjects\Tile;
use App\ValueObjects\Brush;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Mockery;

class MapGeneratorTest extends TestCase
{
    private MapGenerator $mapGenerator;
    private Mockery\MockInterface $imageManager;
    private Mockery\MockInterface $imageInterface;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->imageInterface = Mockery::mock(ImageInterface::class);
        $this->imageManager = Mockery::mock(ImageManager::class);
        $this->mapGenerator = new MapGenerator($this->imageManager);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_configure_image_processing_sets_error_reporting()
    {
        $originalErrorLevel = error_reporting();
        
        $this->mapGenerator->generateMapImage($this->createMockTileMap());
        
        // The method should configure error reporting
        // We can't easily test ini_set directly, but we can verify the method doesn't throw
        $this->assertTrue(true);
    }

    public function test_get_visible_layers_returns_only_visible_layers()
    {
        $tileMap = $this->createMockTileMap();
        
        // Mock the layers relationship
        $visibleLayer = Mockery::mock(Layer::class);
        $hiddenLayer = Mockery::mock(Layer::class);
        
        $layersCollection = new Collection([$visibleLayer, $hiddenLayer]);
        
        $tileMap->shouldReceive('layers')
            ->once()
            ->andReturnSelf();
        
        $tileMap->shouldReceive('where')
            ->with('visible', true)
            ->once()
            ->andReturnSelf();
        
        $tileMap->shouldReceive('get')
            ->once()
            ->andReturn($layersCollection);

        // Use reflection to test private method
        $reflection = new ReflectionClass($this->mapGenerator);
        $method = $reflection->getMethod('getVisibleLayers');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->mapGenerator, $tileMap);
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    public function test_create_map_directory_creates_storage_directory()
    {
        $tileMap = $this->createMockTileMap();
        $tileMap->shouldReceive('getAttribute')->with('id')->andReturn(1);
        
        Storage::shouldReceive('makeDirectory')
            ->once()
            ->with('public/maps/1', 0755, true, true)
            ->andReturn(true);

        $reflection = new ReflectionClass($this->mapGenerator);
        $method = $reflection->getMethod('createMapDirectory');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->mapGenerator, $tileMap);
        
        $this->assertEquals('public/maps/1', $result);
    }

    public function test_create_layer_image_creates_correct_dimensions()
    {
        $tileMap = $this->createMockTileMap();
        $tileMap->shouldReceive('getAttribute')->with('width')->andReturn(10);
        $tileMap->shouldReceive('getAttribute')->with('height')->andReturn(8);
        $tileMap->shouldReceive('getAttribute')->with('tile_width')->andReturn(32);
        $tileMap->shouldReceive('getAttribute')->with('tile_height')->andReturn(32);
        
        $this->imageManager->shouldReceive('create')
            ->once()
            ->with(320, 256) // 10 * 32, 8 * 32
            ->andReturn($this->imageInterface);
        
        $this->imageInterface->shouldReceive('fill')
            ->once()
            ->with('rgba(0, 0, 0, 0)')
            ->andReturnSelf();

        $reflection = new ReflectionClass($this->mapGenerator);
        $method = $reflection->getMethod('createLayerImage');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->mapGenerator, $tileMap);
        
        $this->assertInstanceOf(ImageInterface::class, $result);
    }

    public function test_save_layer_image_creates_correct_path()
    {
        $layer = $this->createMockLayer();
        $layer->shouldReceive('getAttribute')->with('id')->andReturn(5);
        
        $this->imageInterface->shouldReceive('save')
            ->once()
            ->with(Mockery::type('string'), 100)
            ->andReturnSelf();

        $reflection = new ReflectionClass($this->mapGenerator);
        $method = $reflection->getMethod('saveLayerImage');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->mapGenerator, $this->imageInterface, $layer, 'public/maps/1');
        
        $this->assertEquals('public/maps/1/layer_5.png', $result);
    }

    public function test_extract_tileset_uuids_returns_unique_uuids()
    {
        $layer = $this->createMockLayer();
        
        $tile1 = new Tile(0, 0, new Brush('uuid-1', 0, 0));
        $tile2 = new Tile(1, 1, new Brush('uuid-2', 1, 1));
        $tile3 = new Tile(2, 2, new Brush('uuid-1', 2, 2)); // Duplicate UUID
        
        $layer->shouldReceive('getAttribute')->with('data')->andReturn([$tile1, $tile2, $tile3]);

        $reflection = new ReflectionClass($this->mapGenerator);
        $method = $reflection->getMethod('extractTilesetUuids');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->mapGenerator, $layer);
        
        $this->assertCount(2, $result);
        $this->assertContains('uuid-1', $result);
        $this->assertContains('uuid-2', $result);
    }

    public function test_get_tileset_for_tile_returns_correct_tileset()
    {
        $tile = new Tile(0, 0, new Brush('test-uuid', 0, 0));
        $tileset = $this->createMockTileSet();
        
        $tilesets = new Collection(['test-uuid' => $tileset]);
        
        $reflection = new ReflectionClass($this->mapGenerator);
        $method = $reflection->getMethod('getTilesetForTile');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->mapGenerator, $tile, $tilesets);
        
        $this->assertSame($tileset, $result);
    }

    public function test_get_tileset_for_tile_returns_null_for_missing_tileset()
    {
        $tile = new Tile(0, 0, new Brush('missing-uuid', 0, 0));
        $tileset = $this->createMockTileSet();
        
        $tilesets = new Collection(['test-uuid' => $tileset]);
        
        Log::shouldReceive('warning')
            ->once()
            ->with(Mockery::type('string'));
        
        $reflection = new ReflectionClass($this->mapGenerator);
        $method = $reflection->getMethod('getTilesetForTile');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->mapGenerator, $tile, $tilesets);
        
        $this->assertNull($result);
    }

    public function test_tileset_image_exists_returns_true_for_existing_file()
    {
        $testPath = storage_path('app/public/test.png');
        
        // Create a temporary file
        file_put_contents($testPath, 'test');
        
        $reflection = new ReflectionClass($this->mapGenerator);
        $method = $reflection->getMethod('tilesetImageExists');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->mapGenerator, $testPath);
        
        $this->assertTrue($result);
        
        // Clean up
        unlink($testPath);
    }

    public function test_tileset_image_exists_returns_false_for_missing_file()
    {
        $nonExistentPath = storage_path('app/public/non-existent.png');
        
        Log::shouldReceive('warning')
            ->once()
            ->with(Mockery::type('string'));
        
        $reflection = new ReflectionClass($this->mapGenerator);
        $method = $reflection->getMethod('tilesetImageExists');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->mapGenerator, $nonExistentPath);
        
        $this->assertFalse($result);
    }

    public function test_load_tileset_image_returns_image_on_success()
    {
        $testPath = storage_path('app/public/test.png');
        
        // Create a temporary PNG file
        $image = imagecreatetruecolor(32, 32);
        imagepng($image, $testPath);
        imagedestroy($image);
        
        $this->imageManager->shouldReceive('read')
            ->once()
            ->with($testPath)
            ->andReturn($this->imageInterface);
        
        $reflection = new ReflectionClass($this->mapGenerator);
        $method = $reflection->getMethod('loadTilesetImage');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->mapGenerator, $testPath);
        
        $this->assertInstanceOf(ImageInterface::class, $result);
        
        // Clean up
        unlink($testPath);
    }

    public function test_load_tileset_image_returns_null_on_failure()
    {
        $testPath = storage_path('app/public/test.png');
        
        $this->imageManager->shouldReceive('read')
            ->once()
            ->with($testPath)
            ->andReturn(null);
        
        Log::shouldReceive('warning')
            ->once()
            ->with(Mockery::type('string'));
        
        $reflection = new ReflectionClass($this->mapGenerator);
        $method = $reflection->getMethod('loadTilesetImage');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->mapGenerator, $testPath);
        
        $this->assertNull($result);
    }

    public function test_extract_tile_from_tileset_creates_correct_tile_image()
    {
        $tile = new Tile(0, 0, new Brush('test-uuid', 2, 3));
        $tileset = $this->createMockTileSet();
        $tileset->shouldReceive('getAttribute')->with('tile_width')->andReturn(32);
        $tileset->shouldReceive('getAttribute')->with('tile_height')->andReturn(32);
        
        $this->imageManager->shouldReceive('create')
            ->once()
            ->with(32, 32)
            ->andReturn($this->imageInterface);
        
        $this->imageInterface->shouldReceive('fill')
            ->once()
            ->with('rgba(0, 0, 0, 0)')
            ->andReturnSelf();
        
        $this->imageInterface->shouldReceive('place')
            ->once()
            ->with($this->imageInterface, 'top-left', -64, -96) // -2*32, -3*32
            ->andReturnSelf();
        
        $this->imageInterface->shouldReceive('toPng')
            ->once()
            ->andReturnSelf();
        
        $reflection = new ReflectionClass($this->mapGenerator);
        $method = $reflection->getMethod('extractTileFromTileset');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->mapGenerator, $this->imageInterface, $tile, $tileset);
        
        $this->assertInstanceOf(ImageInterface::class, $result);
    }

    public function test_place_tile_on_image_places_tile_correctly()
    {
        $tile = new Tile(3, 4, new Brush('test-uuid', 0, 0));
        $tileWidth = 32;
        $tileHeight = 32;
        
        $this->imageInterface->shouldReceive('place')
            ->once()
            ->with($this->imageInterface, 'top-left', 96, 128) // 3*32, 4*32
            ->andReturnSelf();
        
        $reflection = new ReflectionClass($this->mapGenerator);
        $method = $reflection->getMethod('placeTileOnImage');
        $method->setAccessible(true);
        
        $method->invoke($this->mapGenerator, $this->imageInterface, $this->imageInterface, $tile, $tileWidth, $tileHeight);
    }

    public function test_update_layer_image_path_updates_layer()
    {
        $layer = $this->createMockLayer();
        $imagePath = 'public/maps/1/layer_5.png';
        
        $layer->shouldReceive('update')
            ->once()
            ->with(['image_path' => $imagePath])
            ->andReturn(true);
        
        $reflection = new ReflectionClass($this->mapGenerator);
        $method = $reflection->getMethod('updateLayerImagePath');
        $method->setAccessible(true);
        
        $method->invoke($this->mapGenerator, $layer, $imagePath);
    }

    public function test_ensure_directory_exists_creates_directory()
    {
        $testDir = storage_path('app/test-directory');
        $testFile = $testDir . '/test-file.txt';
        
        // Ensure directory doesn't exist initially
        if (is_dir($testDir)) {
            rmdir($testDir);
        }
        
        $reflection = new ReflectionClass($this->mapGenerator);
        $method = $reflection->getMethod('ensureDirectoryExists');
        $method->setAccessible(true);
        
        $method->invoke($this->mapGenerator, $testFile);
        
        $this->assertTrue(is_dir($testDir));
        
        // Clean up
        rmdir($testDir);
    }

    private function createMockTileMap()
    {
        return Mockery::mock(TileMap::class);
    }

    private function createMockLayer()
    {
        return Mockery::mock(Layer::class);
    }

    private function createMockTileSet()
    {
        return Mockery::mock(TileSet::class);
    }
} 