<?php

namespace Tests\Feature;

use App\Models\TileSet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WizardImportCompleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_can_complete_import_with_new_tileset()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a test JS map file
        $jsContent = "
// Datei: rpg/maps/dalaran.js
// Map: Dalaran Zentrum
// Editor: shizo
// Datum: Sunday 11th of February 2007 16:44:36
var name = 'Test Forest Map';
var width = 10;
var height = 10;
var main_bg = 'forest_mc/1.png';

field_bg[0][0] = 'forest_mc/1.png';
field_bg[0][1] = 'forest_mc/2.png';
field_bg[1][0] = 'forest_mc/3.png';
        ";

        // Upload the map file first (simulating the upload step)
        $mapFile = UploadedFile::fake()->createWithContent('test_map.js', $jsContent);
        $uploadResponse = $this->post('/api/map-import/upload', [
            'files' => [$mapFile]
        ]);

        $uploadResponse->assertStatus(200);
        $uploadData = $uploadResponse->json();
        $filePath = $uploadData['main_map_file'];

        // Create a test tileset image
        $tilesetImage = UploadedFile::fake()->image('forest_mc.png', 256, 256);

        // Test the complete endpoint
        $response = $this->post('/api/map-import/complete', [
            'file_path' => $filePath,
            'format' => 'js',
            'tileset_mappings' => json_encode([
                'forest_mc' => 'create_new'
            ]),
            'tileset_images' => [
                'forest_mc' => $tilesetImage
            ]
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Assert the response structure
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Map imported successfully', $data['message']);
        $this->assertArrayHasKey('map', $data);
        $this->assertArrayHasKey('created_tilesets', $data);

        // Assert the map was created correctly (name comes from JS file)
        $map = $data['map'];
        $this->assertEquals('Test Forest Map', $map['name']);
        $this->assertEquals(10, $map['width']);
        $this->assertEquals(10, $map['height']);

        // Assert the tileset was created
        $this->assertCount(1, $data['created_tilesets']);
        $tileset = $data['created_tilesets'][0];
        $this->assertEquals('forest_mc', $tileset['name']);
        $this->assertEquals('/storage/tilesets/forest_mc.png', $tileset['imageUrl']);

        // Verify the tileset image was saved to public storage
        Storage::disk('public')->assertExists('tilesets/forest_mc.png');

        // Verify the map file was cleaned up
        Storage::disk('local')->assertMissing($filePath);
    }

    public function test_can_complete_import_with_existing_tileset()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create an existing tileset with the same name as in the map
        $existingTileset = TileSet::create([
            'name' => 'forest_mc', // Match the name used in the JS file
            'image_path' => 'tilesets/forest_mc.png',
            'image_width' => 256,
            'image_height' => 256,
            'tile_width' => 32,
            'tile_height' => 32,
            'tile_count' => 64,
            'first_gid' => 1,
            'margin' => 0,
            'spacing' => 0,
        ]);

        // Create a test JS map file
        $jsContent = "
        var name = 'Test Forest Map';
        var width = 8;
        var height = 8;
        var main_bg = 'forest_mc/1.png';
        
        field_bg[0][0] = 'forest_mc/1.png';
        field_bg[0][1] = 'forest_mc/2.png';
        ";

        // Upload the map file first
        $mapFile = UploadedFile::fake()->createWithContent('test_map.js', $jsContent);
        $uploadResponse = $this->post('/api/map-import/upload', [
            'files' => [$mapFile]
        ]);

        $uploadResponse->assertStatus(200);
        $uploadData = $uploadResponse->json();
        $filePath = $uploadData['main_map_file'];

        // Test the complete endpoint with existing tileset mapping
        $response = $this->post('/api/map-import/complete', [
            'file_path' => $filePath,
            'format' => 'js',
            'tileset_mappings' => json_encode([
                'forest_mc' => $existingTileset->uuid
            ]),
            'tileset_images' => []
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Assert the map was created correctly
        $map = $data['map'];
        $this->assertEquals('Test Forest Map', $map['name']);
        $this->assertEquals(8, $map['width']);
        $this->assertEquals(8, $map['height']);

        // Should not create new tilesets since we used existing one
        $this->assertCount(0, $data['created_tilesets']);
    }

    public function test_can_complete_import_with_field_type_file()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create test JS map file
        $jsContent = "
        var name = 'Test Map with Field Types';
        var width = 5;
        var height = 5;
        var main_bg = 'forest_mc/1.png';
        
        field_bg[0][0] = 'forest_mc/1.png';
        ";

        // Create test field type file
        $ftContent = "
        var map_default_x = 10;
        var map_default_y = 10;
        field_type[0] = new Array(1,2,1,2,1);
        field_type[1] = new Array(2,1,2,1,2);
        ";

        // Upload both files
        $mapFile = UploadedFile::fake()->createWithContent('test_map.js', $jsContent);
        $ftFile = UploadedFile::fake()->createWithContent('test_map_ft.js', $ftContent);
        
        $uploadResponse = $this->post('/api/map-import/upload', [
            'files' => [$mapFile, $ftFile]
        ]);

        $uploadResponse->assertStatus(200);
        $uploadData = $uploadResponse->json();
        $filePath = $uploadData['main_map_file'];
        $fieldTypeFilePath = $uploadData['field_type_file'];

        // Create a test tileset image
        $tilesetImage = UploadedFile::fake()->image('forest_mc.png', 256, 256);

        // Test the complete endpoint
        $response = $this->post('/api/map-import/complete', [
            'file_path' => $filePath,
            'format' => 'js',
            'tileset_mappings' => json_encode([
                'forest_mc' => 'create_new'
            ]),
            'field_type_file_path' => $fieldTypeFilePath,
            'tileset_images' => [
                'forest_mc' => $tilesetImage
            ]
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Assert the map was created correctly (name comes from JS file)
        $map = $data['map'];
        $this->assertEquals('Test Map with Field Types', $map['name']);
        $this->assertEquals(5, $map['width']);
        $this->assertEquals(5, $map['height']);

        // Verify both files were cleaned up
        Storage::disk('local')->assertMissing($filePath);
        Storage::disk('local')->assertMissing($fieldTypeFilePath);
    }

    public function test_validates_required_fields()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/api/map-import/complete', []);

        $response->assertStatus(422);
        $data = $response->json();
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('file_path', $data['errors']);
        $this->assertArrayHasKey('format', $data['errors']);
        $this->assertArrayHasKey('tileset_mappings', $data['errors']);
    }

    public function test_validates_file_exists()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/api/map-import/complete', [
            'file_path' => 'nonexistent/file.js',
            'format' => 'js',
            'tileset_mappings' => json_encode([]),
        ]);

        $response->assertStatus(422);
        $data = $response->json();
        $this->assertEquals('File not found', $data['message']);
    }

    public function test_validates_tileset_mappings_format()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a test file
        $jsContent = "var name = 'Test'; var width = 10; var height = 10;";
        Storage::disk('local')->put('imports/test.js', $jsContent);

        $response = $this->post('/api/map-import/complete', [
            'file_path' => 'imports/test.js',
            'format' => 'js',
            'map_name' => 'Test Map',
            'tileset_mappings' => 'invalid json',
        ]);

        $response->assertStatus(422);
        $data = $response->json();
        $this->assertEquals('Invalid tileset mappings format', $data['message']);
    }
} 