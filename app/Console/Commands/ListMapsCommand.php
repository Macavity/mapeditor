<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TileMap;
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
        $limit = (int) $this->option('limit');
        $creatorFilter = $this->option('creator');

        $this->info('Available Tile Maps:');
        $this->line('');

        try {
            $query = TileMap::with(['creator', 'layers']);

            // Apply creator filter if provided
            if ($creatorFilter) {
                $query->whereHas('creator', function ($q) use ($creatorFilter) {
                    $q->where('name', 'like', "%{$creatorFilter}%")
                      ->orWhere('email', 'like', "%{$creatorFilter}%");
                });
            }

            $maps = $query->orderBy('updated_at', 'desc')
                         ->limit($limit)
                         ->get();

            if ($maps->isEmpty()) {
                $this->warn('No maps found.');
                return Command::SUCCESS;
            }

            // Prepare table data
            $headers = ['UUID', 'Name', 'Size', 'Layers', 'Creator', 'Updated'];
            $rows = [];

            foreach ($maps as $map) {
                $layerCounts = $map->layers->groupBy('type')->map->count();
                $layerInfo = collect([
                    $layerCounts->get('background', 0) . 'bg',
                    $layerCounts->get('floor', 0) . 'fl',
                    $layerCounts->get('sky', 0) . 'sky',
                ])->filter(fn($count) => !str_starts_with($count, '0'))->join(', ');

                $rows[] = [
                    substr($map->uuid, 0, 8) . '...',
                    $this->truncate($map->name, 25),
                    "{$map->width}x{$map->height}",
                    $layerInfo ?: 'none',
                    $this->truncate($map->creator?->name ?? 'Unknown', 15),
                    $map->updated_at->format('Y-m-d H:i'),
                ];
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

    /**
     * Truncate a string to specified length.
     */
    private function truncate(string $string, int $length): string
    {
        return strlen($string) > $length 
            ? substr($string, 0, $length - 3) . '...' 
            : $string;
    }
}
