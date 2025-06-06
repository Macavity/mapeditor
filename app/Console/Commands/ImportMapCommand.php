<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MapImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportMapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'map:import 
                            {file : Path to the map file to import}
                            {--format= : Import format (auto-detect if not specified)}
                            {--creator= : Email of the user to set as creator}
                            {--preserve-uuid : Preserve original UUIDs from the import file}
                            {--overwrite : Overwrite existing map if UUID conflicts occur}
                            {--auto-create-tilesets : Automatically create missing tilesets (may not have image files)}
                            {--dry-run : Show what would be imported without actually importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import a tile map from the file system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $importService = app(MapImportService::class);
        
        $filePath = $this->argument('file');
        $format = $this->option('format');
        $creatorEmail = $this->option('creator');
        $preserveUuid = $this->option('preserve-uuid');
        $overwrite = $this->option('overwrite');
        $autoCreateTilesets = $this->option('auto-create-tilesets');
        $dryRun = $this->option('dry-run');

        $this->info("Starting map import...");
        $this->line("File: {$filePath}");

        // Verify file exists
        if (!Storage::exists($filePath) && !file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        // Auto-detect format if not specified
        if (!$format) {
            $format = $importService->detectFormat($filePath);
            if (!$format) {
                $supportedFormats = implode(', ', $importService->getSupportedFormats());
                $this->error("Could not detect file format. Please specify format manually with --format. Supported formats: {$supportedFormats}");
                return Command::FAILURE;
            }
            $this->info("Detected format: {$format}");
        }

        // Validate format
        if (!$importService->isValidFormat($format)) {
            $supportedFormats = implode(', ', $importService->getSupportedFormats());
            $this->error("Unsupported format: {$format}. Supported formats: {$supportedFormats}");
            return Command::FAILURE;
        }

        // Find creator if specified
        $creator = null;
        if ($creatorEmail) {
            $creator = User::where('email', $creatorEmail)->first();
            if (!$creator) {
                $this->error("Creator not found: {$creatorEmail}");
                return Command::FAILURE;
            }
            $this->info("Creator: {$creator->name} ({$creator->email})");
        }

        // Prepare import options
        $options = [
            'preserve_uuid' => $preserveUuid,
            'overwrite' => $overwrite,
            'auto_create_tilesets' => $autoCreateTilesets,
        ];

        try {
            if ($dryRun) {
                return $this->performDryRun($importService, $filePath, $format, $creator, $options);
            }

            // Perform the actual import
            $result = $importService->importFromFile($filePath, $format, $creator, $options);
            $map = $result['map'];
            $tilesetResults = $result['tilesets'];

            $this->info("Map imported successfully!");
            $this->displayImportResult($map, $tilesetResults);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Import failed: " . $e->getMessage());
            
            if ($this->getOutput()->isVerbose()) {
                $this->line($e->getTraceAsString());
            }
            
            return Command::FAILURE;
        }
    }

    /**
     * Perform a dry run to show what would be imported.
     */
    private function performDryRun(MapImportService $importService, string $filePath, string $format, ?User $creator, array $options): int
    {
        $this->info("=== DRY RUN MODE ===");
        $this->info("This will show what would be imported without making changes.");

        try {
            // Get the importer for this format
            $importer = $importService->getSupportedFormats();
            if (!in_array($format, $importer)) {
                throw new \InvalidArgumentException("Format not supported: {$format}");
            }

            // Parse the file to get the data structure
            $importerClass = "App\\Services\\Importers\\" . ucfirst($format) . "MapImporter";
            if (!class_exists($importerClass)) {
                throw new \InvalidArgumentException("Importer class not found: {$importerClass}");
            }

            $importer = new $importerClass();
            $mapData = $importer->parse($filePath);

            // Display what would be imported
            $this->displayImportPreview($mapData, $creator, $options);

            $this->info("=== END DRY RUN ===");
            $this->info("Use the command without --dry-run to perform the actual import.");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Dry run failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Display what would be imported in dry run mode.
     */
    private function displayImportPreview(array $mapData, ?User $creator, array $options): void
    {
        $mapInfo = $mapData['map'];
        
        $this->info("Map Information:");
        $this->line("  Name: " . ($mapInfo['name'] ?? 'Unknown'));
        $this->line("  Size: " . ($mapInfo['width'] ?? 0) . "x" . ($mapInfo['height'] ?? 0) . " tiles");
        $this->line("  Tile Size: " . ($mapInfo['tile_width'] ?? 32) . "x" . ($mapInfo['tile_height'] ?? 32) . " pixels");
        
        if (isset($mapInfo['uuid'])) {
            $this->line("  UUID: " . $mapInfo['uuid']);
            if ($options['preserve_uuid']) {
                $this->line("  ^ UUID will be preserved");
            } else {
                $this->line("  ^ New UUID will be generated");
            }
        }

        $this->line("");
        $this->info("Layers: " . count($mapData['layers'] ?? []));
        foreach (($mapData['layers'] ?? []) as $index => $layer) {
            $this->line("  [{$index}] " . ($layer['name'] ?? 'Unnamed') . " (type: " . ($layer['type'] ?? 'unknown') . ")");
        }

        $this->line("");
        $this->info("Tilesets: " . count($mapData['tilesets'] ?? []));
        foreach (($mapData['tilesets'] ?? []) as $index => $tileset) {
            $name = $tileset['name'] ?? 'Unnamed';
            $uuid = $tileset['uuid'] ?? 'none';
            
            // Check if tileset exists in database
            $exists = $uuid !== 'none' && \App\Models\TileSet::where('uuid', $uuid)->exists();
            $status = $exists ? '✓ exists' : '⚠ missing';
            
            $this->line("  [{$index}] {$name} (UUID: {$uuid}) - {$status}");
        }
        
        if ($options['auto_create_tilesets']) {
            $this->line("");
            $this->comment("Auto-create tilesets is enabled - missing tilesets will be created.");
        } else {
            $missingCount = 0;
            foreach (($mapData['tilesets'] ?? []) as $tileset) {
                $uuid = $tileset['uuid'] ?? null;
                if ($uuid && !\App\Models\TileSet::where('uuid', $uuid)->exists()) {
                    $missingCount++;
                }
            }
            if ($missingCount > 0) {
                $this->line("");
                $this->warn("Warning: {$missingCount} tileset(s) are missing and will cause import to fail.");
                $this->comment("Use --auto-create-tilesets to create them automatically.");
            }
        }

        if ($creator) {
            $this->line("");
            $this->info("Creator: {$creator->name} ({$creator->email})");
        }
    }

    /**
     * Display information about the imported map and tilesets.
     */
    private function displayImportResult($map, array $tilesetResults): void
    {
        $this->line("");
        $this->line("Map Details:");
        $this->line("  UUID: {$map->uuid}");
        $this->line("  Name: {$map->name}");
        $this->line("  Size: {$map->width}x{$map->height} tiles");
        $this->line("  Tile Size: {$map->tile_width}x{$map->tile_height} pixels");
        $this->line("  Layers: " . $map->layers->count());
        
        if ($map->creator) {
            $this->line("  Creator: {$map->creator->name} ({$map->creator->email})");
        }

        $this->line("  Created: " . $map->created_at->format('Y-m-d H:i:s'));

        // Display tileset information
        if (!empty($tilesetResults['created'])) {
            $this->line("");
            $this->info("Created Tilesets:");
            foreach ($tilesetResults['created'] as $tileset) {
                $this->line("  • {$tileset->name} ({$tileset->uuid})");
                if (!$tileset->image_url && !$tileset->image_path) {
                    $this->warn("    ⚠ Warning: No image file specified for this tileset");
                }
            }
        }

        if (!empty($tilesetResults['missing'])) {
            $this->line("");
            $this->comment("Note: Some tilesets from the export were not created (use --auto-create-tilesets to create them):");
            foreach ($tilesetResults['missing'] as $tileset) {
                $name = $tileset['name'] ?? 'Unnamed';
                $this->line("  • {$name} ({$tileset['uuid']})");
            }
        }
    }
} 