<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Enums\LayerType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all tile maps that have field type layers
        $tileMapsWithFieldTypes = DB::table('layers')
            ->where('type', LayerType::FieldType->value)
            ->select('tile_map_id')
            ->distinct()
            ->pluck('tile_map_id');

        foreach ($tileMapsWithFieldTypes as $tileMapId) {
            // Get the highest z-index for this tile map
            $maxZ = DB::table('layers')
                ->where('tile_map_id', $tileMapId)
                ->max('z');

            // Update all field type layers for this tile map to be above all other layers
            DB::table('layers')
                ->where('tile_map_id', $tileMapId)
                ->where('type', LayerType::FieldType->value)
                ->update(['z' => $maxZ + 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as we don't know the original z-indices
        // Field type layers will remain at the top
    }
};
