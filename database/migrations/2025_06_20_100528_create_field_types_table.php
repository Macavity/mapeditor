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
        Schema::create('field_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('color', 7); // Hex color code (e.g., #FF0000)
            $table->timestamps();
        });

        // Insert default field types
        DB::table('field_types')->insert([
            [
                'name' => 'Default',
                'color' => '#00FF00', // Green
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'No Entry',
                'color' => '#FF0000', // Red
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
        Schema::dropIfExists('field_types');
    }
};
