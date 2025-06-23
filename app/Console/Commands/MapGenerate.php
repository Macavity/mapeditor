<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TileMap;
use App\Services\TileMapGenerator;
use App\Repositories\TileMapRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MapGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'map:generate {id?} {--all}';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate images for one or all tile maps';

    /**
     * Execute the console command.
     */
    public function handle(TileMapGenerator $mapGenerator, TileMapRepository $mapRepository)
    {
        if ($this->option('all')) {
            $this->generateAllMaps($mapGenerator);
            return;
        }

        $mapId = $this->argument('id');

        if(!is_numeric($mapId)) {
            $map = $mapRepository->findByUuid($mapId);
            $mapId = $map->id;
        }

        if (!$mapId) {
            $this->error('Either specify a map ID or use --all flag');
            return self::FAILURE;
        }

        $this->generateMap($mapGenerator, $mapId);
    }

    protected function generateAllMaps(TileMapGenerator $mapGenerator): void
    {
        $maps = TileMap::all();
        $bar = $this->output->createProgressBar($maps->count());
        
        $this->info("Generating images for {$maps->count()} maps...");
        
        $bar->start();
        
        foreach ($maps as $map) {
            try {
                $this->generateMap($mapGenerator, $map->id);
            } catch (\Exception $e) {
                $this->error("Error generating map {$map->id}: " . $e->getMessage());
                Log::error("Error generating map {$map->id}", ['exception' => $e]);
            }
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        $this->info('All maps have been processed!');
    }
    
    protected function generateMap(TileMapGenerator $mapGenerator, int $mapId): void
    {
        $map = TileMap::find($mapId);
        
        if (!$map) {
            $this->error("Map with ID {$mapId} not found");
            return;
        }
        
        $this->info("Generating image for map: {$map->name} (ID: {$map->id})");
        
        try {
            $mapGenerator->generateMapImage($map);
            $this->info("Successfully generated image for map: {$map->name}");
        } catch (\Exception $e) {
            $this->error("Failed to generate image for map {$map->name}: " . $e->getMessage());
            Log::error("Failed to generate map image", [
                'map_id' => $map->id,
                'exception' => $e
            ]);
            throw $e;
        }
    }
}
