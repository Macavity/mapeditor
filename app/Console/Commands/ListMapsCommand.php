<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Repositories\MapRepository;
use App\Services\MapDisplayService;
use Illuminate\Console\Command;

class ListMapsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'map:list {--limit=20 : Number of maps to show} {--creator= : Filter by creator name or email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List available tile maps with their details';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $mapRepository = app(MapRepository::class);
        $displayService = app(MapDisplayService::class);
        
        $limit = (int) $this->option('limit');
        $creatorFilter = $this->option('creator');

        $this->info('Available Tile Maps:');
        $this->line('');

        try {
            $maps = $mapRepository->findAll($limit, $creatorFilter);

            if ($maps->isEmpty()) {
                $this->warn('No maps found.');
                return Command::SUCCESS;
            }

            // Prepare table data
            $headers = ['UUID', 'Name', 'Size', 'Layers', 'Creator', 'Updated'];
            $rows = [];

            foreach ($maps as $map) {
                $layerStats = $mapRepository->getLayerStats($map);
                $rows[] = $displayService->prepareMapListRow($map, $layerStats);
            }

            $this->table($headers, $rows);

            $this->line('');
            $this->info("Showing {$maps->count()} of available maps (limit: {$limit})");
            $this->line('');
            $this->comment('To export a map, use: php artisan map:export <full-uuid>');
            $this->comment('To see full UUIDs, use: php artisan map:list --limit=5 | grep -v "+"');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to list maps: " . $e->getMessage());
            return Command::FAILURE;
        }
    }


}
