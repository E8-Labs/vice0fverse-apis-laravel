<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostIntrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post_intrations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('type')->unsigned();
            $table->foreign('type')->references('id')->on('post_intration_types')->onDelete('cascade');

            $table->bigInteger('post_id')->nullable()->unsigned();
            $table->foreign('post_id')->references('id')->on('listing_items')->onDelete('cascade');

            $table->bigInteger('comment_id')->nullable()->unsigned();
            $table->foreign('comment_id')->references('id')->on('post_comments')->onDelete('cascade');

            $table->bigInteger('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('post_intrations');
    }
}
