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
        Schema::create('flagged_listings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_user');
            $table->foreign('from_user')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('listing_id');
            $table->foreign('listing_id')->references('id')->on('listing_items')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flagged_listings');
    }
};
