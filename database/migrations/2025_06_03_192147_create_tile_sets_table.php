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
        Schema::create('tile_sets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->integer('image_width');
            $table->integer('image_height');
            $table->integer('tile_width');
            $table->integer('tile_height');
            $table->integer('tile_count');
            $table->integer('first_gid');
            $table->integer('margin')->default(0);
            $table->integer('spacing')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tile_sets');
    }
};
