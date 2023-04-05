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
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        \DB::table('genres')->insert([
            ['id'=> 1, 'name' => 'Pop'],
            ['id'=> 2, 'name' => 'Hip-Hop'],
            ['id'=> 3, 'name' => 'Electronic Dance Music'],
            ['id'=> 4, 'name' => 'Rock'],
            ['id'=> 5, 'name' => 'Latin'],
            
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('genres');
    }
};
