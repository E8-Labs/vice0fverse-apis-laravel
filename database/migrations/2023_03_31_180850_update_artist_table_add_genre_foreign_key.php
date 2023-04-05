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
        //
        Schema::table('artists', function (Blueprint $table) {
            $table->string('profile_image')->nullable();
            $table->unsignedBigInteger('genre_id')->default(1);
            $table->foreign('genre_id')->references('id')->on('genres')->onDelete('cascade');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
