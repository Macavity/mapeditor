<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TileMap;
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
        $uuidInput = $this->argument('uuid');

        try {
            // Try to find map by full or partial UUID
            $map = TileMap::with(['creator', 'layers'])
                ->where('uuid', 'like', $uuidInput . '%')
                ->first();

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
            $this->line("<comment>Dimensions:</comment> {$map->width} x {$map->height} tiles");
            $this->line("<comment>Tile Size:</comment> {$map->tile_width} x {$map->tile_height} pixels");
            $this->line("<comment>Created:</comment> {$map->created_at->format('Y-m-d H:i:s')}");
            $this->line("<comment>Updated:</comment> {$map->updated_at->format('Y-m-d H:i:s')}");
            
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
                    $tileCount = is_array($layer->data) ? count($layer->data) : 0;
                    
                    $rows[] = [
                        $layer->name,
                        ucfirst($layer->type->value),
                        $layer->z,
                        "{$layer->width}x{$layer->height}",
                        $tileCount,
                        $layer->visible ? 'Yes' : 'No',
                        number_format($layer->opacity, 2),
                    ];
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
