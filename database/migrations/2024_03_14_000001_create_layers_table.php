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
        Schema::create('layers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tile_map_id')->constrained('tile_maps')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['background', 'floor', 'sky', 'field_type'])->default('background');
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->integer('z')->default(0);
            $table->integer('width');
            $table->integer('height');
            $table->json('data');
            $table->boolean('visible')->default(true);
            $table->float('opacity')->default(1.0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layers');
    }
}; 