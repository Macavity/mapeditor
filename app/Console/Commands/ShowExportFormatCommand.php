<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Constants\ExportVersions;
use Illuminate\Console\Command;

class ShowExportFormatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'map:export-formats {--show-structure : Show detailed structure example}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display information about supported export formats and versions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Map Export Format Information");
        $this->line("==============================");

        $this->info("Current Export Version: " . ExportVersions::getCurrent());
        $this->line("");

        $versionInfo = ExportVersions::getVersionInfo();
        
        foreach ($versionInfo as $version => $info) {
            $this->info("Version {$version}:");
            $this->line("  Description: {$info['description']}");
            $this->line("  Features:");
            foreach ($info['features'] as $feature) {
                $this->line("    • {$feature}");
            }
            $this->line("");
        }

        if ($this->option('show-structure')) {
            $this->displayStructureExample();
        }

        return Command::SUCCESS;
    }

    /**
     * Display the export format structure example.
     */
    private function displayStructureExample(): void
    {
        $this->info("Export Format V1.0 Structure:");
        $this->line("==============================");

        $structure = [
            'export_version' => '1.0',
            'map' => [
                'uuid' => 'map-uuid-here',
                'name' => 'My Awesome Map',
                'width' => 32,
                'height' => 24,
                'tile_width' => 32,
                'tile_height' => 32,
                'creator' => [
                    'name' => 'Map Creator',
                    'email' => 'creator@example.com',
                ],
            ],
            'tilesets' => [
                [
                    'uuid' => 'tileset-uuid-here',
                    'name' => 'Terrain Tileset',
                    'image_width' => 512,
                    'image_height' => 512,
                    'tile_width' => 32,
                    'tile_height' => 32,
                    'image_url' => '/images/terrain.png',
                    'image_path' => null,
                    'margin' => 0,
                    'spacing' => 0,
                ],
            ],
            'layers' => [
                [
                    'uuid' => 'layer-uuid-here',
                    'name' => 'Background',
                    'type' => 'background',
                    'x' => 0,
                    'y' => 0,
                    'z' => 0,
                    'width' => 32,
                    'height' => 24,
                    'visible' => true,
                    'opacity' => 1.0,
                    'data' => [
                        [
                            'x' => 5,
                            'y' => 10,
                            'brush' => [
                                'tileset' => 'tileset-uuid-here',
                                'tile_id' => 42,
                            ],
                        ],
                        // ... more tiles
                    ],
                    // ... timestamps
                ],
            ],
        ];

        $json = json_encode($structure, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->line($json);

        $this->line("");
        $this->info("Benefits of the versioned DTO approach:");
        $this->line("• Type safety with PHP 8.1+ readonly classes");
        $this->line("• Clear versioning for backwards compatibility");
        $this->line("• Automatic validation during import/export");
        $this->line("• Rich helper methods for data analysis");
        $this->line("• Easy extension for future format versions");
        $this->line("");
        $this->info("Usage Examples:");
        $this->line("# Export with partial UUID (like map:list shows)");
        $this->line("php artisan map:export abc123");
        $this->line("");
        $this->line("# Export with custom format and path");
        $this->line("php artisan map:export abc123 --format=json --path=backups/");
    }
} 