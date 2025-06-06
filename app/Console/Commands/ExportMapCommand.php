<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TileMap;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportMapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'map:export {uuid : The UUID of the map to export} {--format=json : Export format (json)} {--path= : Custom export path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export a tile map to the file system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $uuid = $this->argument('uuid');
        $format = $this->option('format');
        $customPath = $this->option('path');

        // Validate format
        if (!in_array($format, ['json'])) {
            $this->error("Unsupported format: {$format}. Supported formats: json");
            return Command::FAILURE;
        }

        $this->info("Exporting map: {$uuid}");

        try {
            // Load the map with all relationships
            $map = TileMap::with(['creator', 'layers'])
                ->where('uuid', $uuid)
                ->first();

            if (!$map) {
                $this->error("Map not found: {$uuid}");
                return Command::FAILURE;
            }

            // Prepare export data
            $exportData = $this->prepareExportData($map);

            // Generate filename
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $map->name);
            $filename = "{$sanitizedName}_{$timestamp}.{$format}";

            // Determine export path
            $exportPath = $customPath ?? "exports/maps/{$filename}";

            // Export based on format
            switch ($format) {
                case 'json':
                    $this->exportAsJson($exportData, $exportPath);
                    break;
            }

            $fullPath = Storage::path($exportPath);
            $this->info("Map exported successfully!");
            $this->line("Location: {$fullPath}");
            $this->line("Map: {$map->name} ({$map->uuid})");
            $this->line("Layers: " . $map->layers->count());
            $this->line("Size: {$map->width}x{$map->height} tiles");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Export failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Prepare the map data for export.
     */
    private function prepareExportData(TileMap $map): array
    {
        return [
            'exported_at' => Carbon::now()->toISOString(),
            'export_version' => '1.0',
            'map' => [
                'uuid' => $map->uuid,
                'name' => $map->name,
                'width' => $map->width,
                'height' => $map->height,
                'tile_width' => $map->tile_width,
                'tile_height' => $map->tile_height,
                'created_at' => $map->created_at->toISOString(),
                'updated_at' => $map->updated_at->toISOString(),
                'creator' => $map->creator ? [
                    'id' => $map->creator->id,
                    'name' => $map->creator->name,
                    'email' => $map->creator->email,
                ] : null,
            ],
            'layers' => $map->layers->sortBy('z')->map(function ($layer) {
                return [
                    'uuid' => $layer->uuid,
                    'name' => $layer->name,
                    'type' => $layer->type->value,
                    'x' => $layer->x,
                    'y' => $layer->y,
                    'z' => $layer->z,
                    'width' => $layer->width,
                    'height' => $layer->height,
                    'visible' => $layer->visible,
                    'opacity' => $layer->opacity,
                    'data' => $layer->data,
                    'created_at' => $layer->created_at->toISOString(),
                    'updated_at' => $layer->updated_at->toISOString(),
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Export data as JSON.
     */
    private function exportAsJson(array $data, string $path): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        if ($json === false) {
            throw new \RuntimeException('Failed to encode data as JSON');
        }

        Storage::put($path, $json);
    }
}
