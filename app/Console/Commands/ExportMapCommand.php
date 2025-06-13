<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Repositories\MapRepository;
use App\Services\MapExportService;
use App\DataTransferObjects\Export\ExportMapFormatV1;
use Illuminate\Console\Command;

class ExportMapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'map:export {uuid : The (partial) UUID of the map to export} {--format=json : Export format (json, tmx)} {--path= : Custom export path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export a tile map to the file system';

    /**
     * Execute the console command.
     */
    public function handle(MapRepository $mapRepository): int
    {
        $uuid = $this->argument('uuid');
        $format = $this->option('format');
        $customPath = $this->option('path');

        // Create export service with command output
        $exportService = new MapExportService($this->output);

        if (!$exportService->isValidFormat($format)) {
            $supportedFormats = implode(', ', $exportService->getSupportedFormats());
            $this->error("Unsupported format: {$format}. Supported formats: {$supportedFormats}");
            return Command::FAILURE;
        }

        $this->info("Looking for map with UUID starting with: {$uuid}");

        try {
            $map = $mapRepository->findByUuid($uuid);

            if (!$map) {
                $this->error("Map not found with UUID starting with: {$uuid}");
                $this->line('');
                $this->comment('Use "php artisan map:list" to see available maps.');
                return Command::FAILURE;
            }

            $this->info("Found map: {$map->name} ({$map->uuid})");

            // Prepare export data
            $exportData = $exportService->prepareExportData($map);

            // Generate filename and path
            $filename = $exportService->generateFilename($map, $format);
            $exportPath = $customPath ?? $exportService->getDefaultExportPath($filename);

            // Export based on format
            switch ($format) {
                case 'json':
                    $exportService->exportAsJson($exportData, $exportPath);
                    break;
                case 'tmx':
                    $exportService->exportAsTmx($exportData, $exportPath);
                    break;
            }

            $fullPath = $exportService->getFullPath($exportPath);
            
            $this->info("Map exported successfully!");
            $this->line("Location: {$fullPath}");
            $this->line("");
            $this->displayExportSummary($exportData);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Export failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Display export summary information.
     */
    private function displayExportSummary(ExportMapFormatV1 $exportData): void
    {
        $map = $exportData->map;
        
        $this->line("Map: {$map->name} ({$map->uuid})");
        $this->line("Export Version: {$exportData->export_version}");
        $this->line("Size: {$map->width}x{$map->height} tiles ({$map->getTotalTiles()} total)");
        $this->line("Tile Size: {$map->tile_width}x{$map->tile_height} pixels");
        $this->line("Layers: " . count($exportData->layers));
        $this->line("Tilesets: " . count($exportData->tilesets));
        
        if ($map->hasCreator()) {
            $this->line("Creator: {$map->creator->name} ({$map->creator->email})");
        }

        // Display layer summary
        if (!empty($exportData->layers)) {
            $this->line("");
            $this->line("Layer Details:");
            foreach ($exportData->layers as $index => $layer) {
                $fillPercentage = number_format($layer->getFillPercentage(), 1);
                $visibility = $layer->isHidden() ? '(hidden)' : '';
                $opacity = $layer->isTransparent() ? " (opacity: {$layer->opacity})" : '';
                
                $this->line("  [{$index}] {$layer->name} ({$layer->type}) - {$layer->getTileCount()} tiles ({$fillPercentage}% filled){$visibility}{$opacity}");
            }
        }
    }
}
