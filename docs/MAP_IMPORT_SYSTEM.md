# Map Import System

The map import system provides a flexible, extensible way to import tile maps from various file formats into your map editor.

## Usage

### Basic Import

```bash
# Import a JSON map file
php artisan map:import path/to/map.json

# Import with format specification
php artisan map:import path/to/map.tmx --format=tmx

# Import with a specific creator
php artisan map:import path/to/map.json --creator=user@example.com

# Auto-create missing tilesets (use with caution - image files may not exist)
php artisan map:import path/to/map.json --auto-create-tilesets
```

### Advanced Options

```bash
# Preserve original UUIDs from import file
php artisan map:import path/to/map.json --preserve-uuid

# Overwrite existing maps with same UUID
php artisan map:import path/to/map.json --preserve-uuid --overwrite

# Dry run to see what would be imported
php artisan map:import path/to/map.json --dry-run
```

### Complete Example

```bash
php artisan map:import exports/maps/MyMap_2024-01-15_14-30-00.json \
    --creator=admin@example.com \
    --preserve-uuid \
    --dry-run
```

## Supported Formats

- **JSON**: Native format exported by the map editor
- **TMX**: Tiled Map Editor format (basic support)

## Architecture

The import system uses a pluggable architecture with clear separation of concerns:

### Core Components

1. **MapImportService**: Main orchestrator that handles format detection, validation, and import coordination
2. **ImporterInterface**: Contract that all format importers must implement
3. **Format Importers**: Specific handlers for each file format (JsonMapImporter, TmxMapImporter, etc.)
4. **ImportMapCommand**: CLI interface for importing maps

### Data Flow

```
File Input → Format Detection → Parser → Validation → Database Import
```

## Adding New Formats

Adding support for a new file format is straightforward:

### 1. Create an Importer Class

```php
<?php

namespace App\Services\Importers;

class MyFormatImporter implements ImporterInterface
{
    public function parse(string $filePath): array
    {
        // Read and parse the file
        $content = file_get_contents($filePath);
        return $this->parseString($content);
    }

    public function parseString(string $data): array
    {
        // Parse the raw data and return normalized map structure
        return [
            'map' => [
                'name' => 'Imported Map',
                'width' => 32,
                'height' => 24,
                'tile_width' => 32,
                'tile_height' => 32,
            ],
            'layers' => [
                // Layer data...
            ],
            'tilesets' => [
                // Tileset data...
            ],
        ];
    }

    public function canHandle(string $filePath): bool
    {
        return pathinfo($filePath, PATHINFO_EXTENSION) === 'myformat';
    }

    public function getSupportedExtensions(): array
    {
        return ['myformat'];
    }

    public function getName(): string
    {
        return 'My Format Importer';
    }

    public function getDescription(): string
    {
        return 'Imports maps from My Format files';
    }
}
```

### 2. Register the Importer

In `MapImportService::__construct()`:

```php
$this->registerImporter('myformat', new MyFormatImporter());
```

### 3. That's It!

Your new format is now supported by the import system and will work with:

- Automatic format detection
- The CLI command
- All existing options (dry-run, preserve-uuid, etc.)

## Data Structure

Importers must return data in this normalized structure:

```php
[
    'map' => [
        'name' => 'Map Name',
        'width' => 32,              // Width in tiles
        'height' => 24,             // Height in tiles
        'tile_width' => 32,         // Tile width in pixels
        'tile_height' => 32,        // Tile height in pixels
        'uuid' => 'optional-uuid',  // Optional: preserve original UUID
    ],
    'layers' => [
        [
            'name' => 'Layer Name',
            'type' => 'floor',       // floor, background, sky, field_type
            'x' => 0,
            'y' => 0,
            'z' => 0,                // Z-index for layer ordering
            'width' => 32,
            'height' => 24,
            'visible' => true,
            'opacity' => 1.0,
            'data' => [              // Array of tile placements
                [
                    'x' => 5,
                    'y' => 10,
                    'brush' => [
                        'tileset' => 'tileset-uuid',
                        'tile_id' => 42,
                    ]
                ],
                // More tiles...
            ],
            'uuid' => 'optional-uuid', // Optional: preserve original UUID
        ],
        // More layers...
    ],
    'tilesets' => [
        [
            'name' => 'Tileset Name',
            'image_width' => 512,
            'image_height' => 512,
            'tile_width' => 32,
            'tile_height' => 32,
            'image_url' => 'path/to/image.png',
            'tile_count' => 256,
            'first_gid' => 1,
            'margin' => 0,
            'spacing' => 0,
            'uuid' => 'optional-uuid', // Optional: preserve original UUID
        ],
        // More tilesets...
    ],
]
```

## Tileset Handling

When importing maps, tilesets referenced in the file may or may not exist in your database:

### **Default Behavior (Safe)**

- Import **fails** if any tilesets are missing
- Clear error message lists missing tilesets
- Prevents broken maps with missing assets

### **Auto-Create Mode**

```bash
php artisan map:import path/to/map.json --auto-create-tilesets
```

- Creates missing tileset records automatically
- ⚠️ **Warning**: Image files may not exist, causing rendering issues
- Useful for development or when you'll add images later

### **Dry-Run Preview**

```bash
php artisan map:import path/to/map.json --dry-run
```

- Shows which tilesets exist (✓) vs missing (⚠)
- Warns about potential import failures
- Suggests using `--auto-create-tilesets` if needed

## Error Handling

The import system provides comprehensive error handling:

- **File not found**: Clear error message with file path
- **Invalid format**: Lists supported formats
- **Malformed data**: Specific validation errors
- **UUID conflicts**: Options to overwrite or generate new UUIDs
- **Missing tilesets**: Lists missing tilesets with auto-create option
- **Database errors**: Transaction rollback on failure

## Best Practices

1. **Always use dry-run first** to verify imports
2. **Preserve UUIDs** when migrating between environments
3. **Specify creators** to maintain ownership
4. **Validate files** before importing large batches
5. **Use transactions** for consistency (handled automatically)

## Examples

### Exporting and Re-importing

```bash
# Export a map (supports partial UUIDs)
php artisan map:export abc123 --format=json --path=backup/

# Later, import it back
php artisan map:import backup/MyMap_2024-01-15_14-30-00.json --preserve-uuid
```

### Batch Operations

```bash
# Preview multiple imports
for file in exports/maps/*.json; do
    php artisan map:import "$file" --dry-run
done

# Preview them first to check for tileset issues
for file in exports/maps/*.json; do
    php artisan map:import "$file" --dry-run
done

# Import them all with auto-create tilesets
for file in exports/maps/*.json; do
    php artisan map:import "$file" --creator=admin@example.com --auto-create-tilesets
done
```
