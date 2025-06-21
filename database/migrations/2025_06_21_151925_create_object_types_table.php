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
        Schema::create('object_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color', 7); // Hex color code (e.g., #FF0000)
            $table->text('description')->nullable(); // Optional description
            $table->boolean('is_solid')->default(true); // Whether objects of this type block movement
            $table->timestamps();
        });

        // Insert default object type
        DB::table('object_types')->insert([
            [
                'name' => 'Player',
                'color' => '#FF0000', // Red
                'description' => 'Player character that can move around the map',
                'is_solid' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('object_types');
    }
};
