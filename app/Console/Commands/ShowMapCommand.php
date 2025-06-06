<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Repositories\MapRepository;
use App\Services\MapDisplayService;
use Illuminate\Console\Command;

class ShowMapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'map:show {uuid : The UUID of the map to show (can be partial)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show detailed information about a specific tile map';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $mapRepository = app(MapRepository::class);
        $displayService = app(MapDisplayService::class);
        
        $uuidInput = $this->argument('uuid');

        try {
            $map = $mapRepository->findByUuid($uuidInput);

            if (!$map) {
                $this->error("Map not found with UUID starting with: {$uuidInput}");
                $this->line('');
                $this->comment('Use "php artisan map:list" to see available maps.');
                return Command::FAILURE;
            }

            // Map Information
            $this->info("Map Details:");
            $this->line('');
            
            $this->line("<comment>UUID:</comment> {$map->uuid}");
            $this->line("<comment>Name:</comment> {$map->name}");
            $this->line("<comment>Dimensions:</comment> " . $displayService->formatDimensions($map) . " tiles");
            $this->line("<comment>Tile Size:</comment> " . $displayService->formatTileSize($map));
            $this->line("<comment>Created:</comment> " . $displayService->formatDate($map->created_at, 'Y-m-d H:i:s'));
            $this->line("<comment>Updated:</comment> " . $displayService->formatDate($map->updated_at, 'Y-m-d H:i:s'));
            
            if ($map->creator) {
                $this->line("<comment>Creator:</comment> {$map->creator->name} ({$map->creator->email})");
            }

            // Layer Information
            $this->line('');
            $this->info("Layers ({$map->layers->count()}):");
            
            if ($map->layers->isEmpty()) {
                $this->line('  No layers found.');
            } else {
                $headers = ['Name', 'Type', 'Z-Index', 'Size', 'Tiles', 'Visible', 'Opacity'];
                $rows = [];

                foreach ($map->layers->sortBy('z') as $layer) {
                    $rows[] = $displayService->prepareLayerDetailRow($layer);
                }

                $this->table($headers, $rows);
            }

            // Export Command
            $this->line('');
            $this->comment("To export this map:");
            $this->line("php artisan map:export {$map->uuid}");
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to show map: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
