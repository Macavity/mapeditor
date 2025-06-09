<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tile_maps', function (Blueprint $table) {
            $table->string('external_creator')->nullable()->after('creator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tile_maps', function (Blueprint $table) {
            $table->dropColumn('external_creator');
        });
    }
};
