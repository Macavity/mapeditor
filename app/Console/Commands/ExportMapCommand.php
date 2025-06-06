<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Repositories\MapRepository;
use App\Services\MapExportService;
use Illuminate\Console\Command;

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
        $mapRepository = app(MapRepository::class);
        $exportService = app(MapExportService::class);
        
        $uuid = $this->argument('uuid');
        $format = $this->option('format');
        $customPath = $this->option('path');

        // Validate format
        if (!$exportService->isValidFormat($format)) {
            $supportedFormats = implode(', ', $exportService->getSupportedFormats());
            $this->error("Unsupported format: {$format}. Supported formats: {$supportedFormats}");
            return Command::FAILURE;
        }

        $this->info("Exporting map: {$uuid}");

        try {
            $map = $mapRepository->findByExactUuid($uuid);

            if (!$map) {
                $this->error("Map not found: {$uuid}");
                return Command::FAILURE;
            }

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
            }

            $fullPath = $exportService->getFullPath($exportPath);
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


}
