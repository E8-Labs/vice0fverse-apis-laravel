<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post_comments', function (Blueprint $table) {
            $table->id();
            $table->longText('comment');

            $table->bigInteger('reply_to')->nullable()->unsigned();
            $table->foreign('reply_to')->references('id')->on('post_comments')->nullOnDelete();

            $table->bigInteger('mention_to')->nullable()->unsigned();
            $table->foreign('mention_to')->references('id')->on('post_comments')->nullOnDelete();

            $table->bigInteger('post_id')->nullable()->unsigned();
            $table->foreign('post_id')->references('id')->on('listing_items')->onDelete('cascade');

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
        Schema::dropIfExists('post_comments');
    }
}
