<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite compatibility, we need to recreate the table
        if (DB::connection()->getDriverName() === 'sqlite') {
            // Create a new table with the updated enum and all columns
            Schema::create('layers_new', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('tile_map_id');
                $table->foreign('tile_map_id')
                    ->references('id')
                    ->on('tile_maps')
                    ->onDelete('cascade');
                $table->string('name');
                $table->enum('type', ['background', 'floor', 'sky', 'field_type', 'object'])->default('background');
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

            // Copy data from old table to new table (explicit columns)
            DB::statement('INSERT INTO layers_new (id, uuid, tile_map_id, name, type, x, y, z, width, height, data, visible, opacity, created_at, updated_at, deleted_at) SELECT id, uuid, tile_map_id, name, type, x, y, z, width, height, data, visible, opacity, created_at, updated_at, deleted_at FROM layers');

            // Drop old table and rename new table
            Schema::drop('layers');
            Schema::rename('layers_new', 'layers');
        } else {
            // For MySQL, use the original approach
            DB::statement("ALTER TABLE layers MODIFY COLUMN type ENUM('background', 'floor', 'sky', 'field_type', 'object') DEFAULT 'background'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // Create a new table with the original enum and all columns
            Schema::create('layers_old', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('tile_map_id');
                $table->foreign('tile_map_id')
                    ->references('id')
                    ->on('tile_maps')
                    ->onDelete('cascade');
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

            // Copy data from current table to old table (excluding 'object' type, explicit columns)
            DB::statement("INSERT INTO layers_old (id, uuid, tile_map_id, name, type, x, y, z, width, height, data, visible, opacity, created_at, updated_at, deleted_at) SELECT id, uuid, tile_map_id, name, type, x, y, z, width, height, data, visible, opacity, created_at, updated_at, deleted_at FROM layers WHERE type != 'object'");

            // Drop current table and rename old table
            Schema::drop('layers');
            Schema::rename('layers_old', 'layers');
        } else {
            // For MySQL, use the original approach
            DB::statement("ALTER TABLE layers MODIFY COLUMN type ENUM('background', 'floor', 'sky', 'field_type') DEFAULT 'background'");
        }
    }
};
