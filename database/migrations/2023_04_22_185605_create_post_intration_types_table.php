<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostIntrationTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post_intration_types', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("comment");
            $table->timestamps();
        });
        DB::table('post_intration_types')->insert(
            array(
                ['name' => "Like", "comment" => 'If a post is liked'],
                ["name" => "Comment", "comment" => 'If a comment is made on a post'],
                ["name" => "Share", "comment" => 'If a post is shared'],
                ["name" => "PostView", "comment" => 'If a post is Viewed'],
            )
         );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('post_intration_types');
    }
}
