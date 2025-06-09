<?php

declare(strict_types=1);

namespace App\Services\Importers;

use Illuminate\Support\Facades\Storage;

class TmxMapImporter implements ImporterInterface
{
    /**
     * Parse a TMX map file and return structured data.
     */
    public function parse(string $filePath): array
    {
        // Try to read from Laravel storage first, then fallback to filesystem
        if (Storage::exists($filePath)) {
            $content = Storage::get($filePath);
        } elseif (file_exists($filePath)) {
            $content = file_get_contents($filePath);
        } else {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        if ($content === false) {
            throw new \InvalidArgumentException("Failed to read file: {$filePath}");
        }

        return $this->parseString($content);
    }

    /**
     * Parse raw TMX data string and return structured data.
     */
    public function parseString(string $data): array
    {
        // Disable libxml errors and use internal error handling
        $prevUseErrors = libxml_use_internal_errors(true);
        $prevDisableEntities = libxml_disable_entity_loader(true);

        try {
            $xml = simplexml_load_string($data);
            
            if ($xml === false) {
                $errors = libxml_get_errors();
                $errorMessage = "Invalid XML data";
                if (!empty($errors)) {
                    $errorMessage .= ": " . $errors[0]->message;
                }
                throw new \InvalidArgumentException($errorMessage);
            }

            return $this->convertXmlToMapData($xml);

        } finally {
            // Restore previous libxml settings
            libxml_use_internal_errors($prevUseErrors);
            libxml_disable_entity_loader($prevDisableEntities);
        }
    }

    /**
     * Check if this importer can handle the given file.
     */
    public function canHandle(string $filePath): bool
    {
        // Check file extension
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->getSupportedExtensions())) {
            return false;
        }

        // Try to peek at the file content to verify it's valid TMX XML
        try {
            if (Storage::exists($filePath)) {
                $content = Storage::get($filePath);
            } elseif (file_exists($filePath)) {
                $content = file_get_contents($filePath);
            } else {
                return false;
            }

            if ($content === false) {
                return false;
            }

            // Check if it looks like TMX format
            return strpos($content, '<map') !== false && strpos($content, 'version=') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the supported file extensions for this importer.
     */
    public function getSupportedExtensions(): array
    {
        return ['tmx'];
    }

    /**
     * Get a human-readable name for this importer.
     */
    public function getName(): string
    {
        return 'TMX Map Importer';
    }

    /**
     * Get a description of what this importer handles.
     */
    public function getDescription(): string
    {
        return 'Imports tile maps from TMX files (Tiled Map Editor format)';
    }

    /**
     * Convert TMX XML structure to our internal map data format.
     */
    private function convertXmlToMapData(\SimpleXMLElement $xml): array
    {
        $attributes = $xml->attributes();
        
        $mapData = [
            'name' => (string) ($attributes['name'] ?? 'Imported TMX Map'),
            'width' => (int) $attributes['width'],
            'height' => (int) $attributes['height'],
            'tile_width' => (int) $attributes['tilewidth'],
            'tile_height' => (int) $attributes['tileheight'],
        ];

        $layers = [];
        $tilesets = [];

        // Parse tilesets
        foreach ($xml->tileset as $tilesetXml) {
            $tilesets[] = $this->parseTileset($tilesetXml);
        }

        // Parse layers
        $zIndex = 0;
        foreach ($xml->layer as $layerXml) {
            $layers[] = $this->parseLayer($layerXml, $mapData, $zIndex++);
        }

        return [
            'map' => $mapData,
            'layers' => $layers,
            'tilesets' => $tilesets,
        ];
    }

    /**
     * Parse a TMX tileset element.
     */
    private function parseTileset(\SimpleXMLElement $tilesetXml): array
    {
        $attributes = $tilesetXml->attributes();
        $imageElement = $tilesetXml->image;
        $imageAttributes = $imageElement ? $imageElement->attributes() : null;

        return [
            'uuid' => null, // TMX doesn't have UUIDs, will be generated
            'name' => (string) ($attributes['name'] ?? 'Unnamed Tileset'),
            'first_gid' => (int) $attributes['firstgid'],
            'tile_width' => (int) $attributes['tilewidth'],
            'tile_height' => (int) $attributes['tileheight'],
            'tile_count' => (int) $attributes['tilecount'],
            'image_width' => $imageAttributes ? (int) $imageAttributes['width'] : 0,
            'image_height' => $imageAttributes ? (int) $imageAttributes['height'] : 0,
            'image_path' => $imageAttributes ? (string) $imageAttributes['source'] : null,
            'margin' => (int) ($attributes['margin'] ?? 0),
            'spacing' => (int) ($attributes['spacing'] ?? 0),
        ];
    }

    /**
     * Parse a TMX layer element.
     */
    private function parseLayer(\SimpleXMLElement $layerXml, array $mapData, int $zIndex): array
    {
        $attributes = $layerXml->attributes();
        
        $layer = [
            'uuid' => null, // TMX doesn't have UUIDs, will be generated
            'name' => (string) ($attributes['name'] ?? 'Unnamed Layer'),
            'type' => 'floor', // Default type, could be enhanced with TMX properties
            'x' => (int) ($attributes['offsetx'] ?? 0),
            'y' => (int) ($attributes['offsety'] ?? 0),
            'z' => $zIndex,
            'width' => (int) ($attributes['width'] ?? $mapData['width']),
            'height' => (int) ($attributes['height'] ?? $mapData['height']),
            'visible' => !isset($attributes['visible']) || (string) $attributes['visible'] !== '0',
            'opacity' => (float) ($attributes['opacity'] ?? 1.0),
            'data' => [],
        ];

        // Parse layer data
        $dataElement = $layerXml->data;
        if ($dataElement) {
            $layer['data'] = $this->parseLayerData($dataElement, $layer['width'], $layer['height']);
        }

        return $layer;
    }

    /**
     * Parse TMX layer data (supports CSV format for simplicity).
     */
    private function parseLayerData(\SimpleXMLElement $dataElement, int $width, int $height): array
    {
        $attributes = $dataElement->attributes();
        $encoding = (string) ($attributes['encoding'] ?? '');
        
        $data = [];
        
        if ($encoding === 'csv') {
            // Parse CSV data
            $csvData = trim((string) $dataElement);
            $values = array_map('intval', explode(',', $csvData));
            
            // Convert flat array to tile positions with basic structure
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $index = $y * $width + $x;
                    $gid = $values[$index] ?? 0;
                    
                    if ($gid > 0) {
                        $data[] = [
                            'x' => $x,
                            'y' => $y,
                            'brush' => [
                                'tileset' => null, // Would need tileset mapping logic
                                'tile_id' => $gid,
                            ]
                        ];
                    }
                }
            }
        } else {
            // For other encodings (base64, etc.), you'd implement additional parsing
            throw new \InvalidArgumentException("TMX encoding '{$encoding}' is not yet supported. Please use CSV encoding.");
        }

        return $data;
    }
} 