<?php

declare(strict_types=1);

namespace App\Services\Importers;

use App\DataTransferObjects\Export\ExportMapFormatV1;
use App\Constants\ExportVersions;
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

        // Try to peek at the file content to verify it's valid V1 JSON format
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

            // Check if it's valid JSON and has V1 format structure
            $sample = substr($content, 0, 2000); // Check first 2KB for better format detection
            $decoded = json_decode($sample, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }

            // Check for V1 format markers
            return is_array($decoded) && 
                   isset($decoded['export_version']) && 
                   ExportVersions::isSupported($decoded['export_version']) &&
                   isset($decoded['map']);
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
        return 'Imports tile maps from V1 JSON format files exported by the map editor';
    }

    /**
     * Normalize and validate the parsed JSON data structure.
     */
    private function normalizeMapData(array $data): array
    {
        // Only support V1 versioned export format
        if (!isset($data['export_version'])) {
            throw new \InvalidArgumentException("Missing export_version field. Only V1 format is supported.");
        }

        if (!isset($data['map']) || !isset($data['layers'])) {
            throw new \InvalidArgumentException("Invalid V1 format. Missing required 'map' or 'layers' field.");
        }

        return $this->normalizeV1Format($data);
    }

    /**
     * Normalize the V1 export format using DTO validation.
     */
    private function normalizeV1Format(array $data): array
    {
        $version = $data['export_version'];
        
        // Validate version is supported
        if (!ExportVersions::isSupported($version)) {
            $supportedVersions = implode(', ', ExportVersions::getSupportedVersions());
            throw new \InvalidArgumentException("Unsupported export version: {$version}. Supported versions: {$supportedVersions}");
        }

        // Use DTO for validation and normalization
        try {
            $dto = ExportMapFormatV1::fromArray($data);
            return $dto->toArray();
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid V1 format structure: " . $e->getMessage());
        }
    }

    /**
     * Parse a JSON map file for the import wizard, returning basic information and tileset usage.
     * This method is optimized for the wizard and doesn't build complete tileset models.
     */
    public function parseForWizard(string $filePath): array
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

        return $this->parseStringForWizard($content);
    }

    /**
     * Parse raw JSON data string for the wizard, returning basic information and tileset usage.
     */
    public function parseStringForWizard(string $data): array
    {
        $decodedData = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException("Invalid JSON data: " . json_last_error_msg());
        }

        if (!is_array($decodedData)) {
            throw new \InvalidArgumentException("JSON data must be an object/array");
        }

        // Extract basic map information
        $mapInfo = $decodedData['map'] ?? [];
        $layers = $decodedData['layers'] ?? [];
        $tilesets = $decodedData['tilesets'] ?? [];

        // Build tileset suggestions
        $tilesetSuggestions = $this->buildTilesetSuggestions($tilesets);

        return [
            'map_info' => [
                'name' => $mapInfo['name'] ?? 'Unknown Map',
                'width' => $mapInfo['width'] ?? 0,
                'height' => $mapInfo['height'] ?? 0,
                'external_creator' => $mapInfo['external_creator'] ?? null,
                'has_field_types' => false, // JSON format doesn't have field types
            ],
            'tilesets' => $tilesetSuggestions,
            'field_type_file' => null,
        ];
    }

    /**
     * Build tileset suggestions based on tileset data.
     */
    private function buildTilesetSuggestions(array $tilesets): array
    {
        $suggestions = [];
        
        foreach ($tilesets as $tileset) {
            $originalName = $tileset['name'] ?? 'Unknown';
            $formattedName = $this->formatTilesetName($originalName);
            
            // Check if tileset image exists
            $imageExists = $this->checkTilesetImageExists($originalName);
            
            // Try to find existing tileset by name
            $existingTileset = $this->findExistingTileset($originalName, $formattedName);
            
            $suggestions[] = [
                'original_name' => $originalName,
                'formatted_name' => $formattedName,
                'tile_count' => 0, // Would need to scan layers to count tiles
                'tile_ids' => [],
                'max_tile_id' => 0,
                'image_exists' => $imageExists,
                'existing_tileset' => $existingTileset ? [
                    'uuid' => $existingTileset->uuid,
                    'name' => $existingTileset->name,
                    'image_path' => $existingTileset->image_path,
                ] : null,
                'requires_upload' => !$imageExists && !$existingTileset,
            ];
        }
        
        return $suggestions;
    }

    /**
     * Check if a tileset image file exists.
     */
    private function checkTilesetImageExists(string $tilesetName): bool
    {
        $basename = $tilesetName . '.png';
        $searchDirs = [
            base_path('tilesets/' . $basename),
            base_path('tests/static/tilesets/' . $basename),
        ];
        
        foreach ($searchDirs as $src) {
            if (file_exists($src)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Find existing tileset by name.
     */
    private function findExistingTileset(string $originalName, string $formattedName): ?\App\Models\TileSet
    {
        return \App\Models\TileSet::where('name', $originalName)
            ->orWhere('name', $formattedName)
            ->first();
    }

    /**
     * Format tileset name for consistency.
     */
    private function formatTilesetName(string $tilesetName): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $tilesetName));
    }
} 