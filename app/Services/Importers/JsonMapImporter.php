<?php

declare(strict_types=1);

namespace App\Services\Importers;

use Illuminate\Support\Facades\Storage;

class JsonMapImporter implements ImporterInterface
{
    /**
     * Parse a JSON map file and return structured data.
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
     * Parse raw JSON data string and return structured data.
     */
    public function parseString(string $data): array
    {
        $decodedData = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException("Invalid JSON data: " . json_last_error_msg());
        }

        if (!is_array($decodedData)) {
            throw new \InvalidArgumentException("JSON data must be an object/array");
        }

        // Validate and normalize the data structure
        return $this->normalizeMapData($decodedData);
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

        // Try to peek at the file content to verify it's valid JSON
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

            // Just check if it's valid JSON - don't parse the whole thing
            $sample = substr($content, 0, 1000); // Check first 1KB
            json_decode($sample);
            return json_last_error() === JSON_ERROR_NONE;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the supported file extensions for this importer.
     */
    public function getSupportedExtensions(): array
    {
        return ['json'];
    }

    /**
     * Get a human-readable name for this importer.
     */
    public function getName(): string
    {
        return 'JSON Map Importer';
    }

    /**
     * Get a description of what this importer handles.
     */
    public function getDescription(): string
    {
        return 'Imports tile maps from JSON files exported by the map editor';
    }

    /**
     * Normalize and validate the parsed JSON data structure.
     */
    private function normalizeMapData(array $data): array
    {
        // Handle different JSON structures that might be exported
        
        // If this looks like our standard export format
        if (isset($data['map']) && isset($data['layers'])) {
            return $this->normalizeStandardFormat($data);
        }

        // If this looks like a direct map object (legacy or different export)
        if (isset($data['name']) && isset($data['width']) && isset($data['height'])) {
            return $this->normalizeLegacyFormat($data);
        }

        throw new \InvalidArgumentException("Unrecognized JSON map format");
    }

    /**
     * Normalize the standard export format (current format).
     */
    private function normalizeStandardFormat(array $data): array
    {
        $normalized = [
            'map' => $data['map'],
            'layers' => $data['layers'] ?? [],
            'tilesets' => $data['tilesets'] ?? [],
        ];

        // Ensure required map fields have defaults
        $normalized['map'] = array_merge([
            'tile_width' => 32,
            'tile_height' => 32,
        ], $normalized['map']);

        // Normalize layers
        $normalized['layers'] = array_map(function ($layer) {
            return array_merge([
                'type' => 'floor',
                'x' => 0,
                'y' => 0,
                'z' => 0,
                'visible' => true,
                'opacity' => 1.0,
                'data' => [],
            ], $layer);
        }, $normalized['layers']);

        return $normalized;
    }

    /**
     * Normalize legacy or different format.
     */
    private function normalizeLegacyFormat(array $data): array
    {
        $mapData = [
            'name' => $data['name'],
            'width' => $data['width'],
            'height' => $data['height'],
            'tile_width' => $data['tile_width'] ?? 32,
            'tile_height' => $data['tile_height'] ?? 32,
        ];

        // Preserve UUID if present
        if (isset($data['uuid'])) {
            $mapData['uuid'] = $data['uuid'];
        }

        $layers = [];
        if (isset($data['layers']) && is_array($data['layers'])) {
            $layers = $data['layers'];
        }

        $tilesets = [];
        if (isset($data['tilesets']) && is_array($data['tilesets'])) {
            $tilesets = $data['tilesets'];
        }

        return [
            'map' => $mapData,
            'layers' => $layers,
            'tilesets' => $tilesets,
        ];
    }
} 