<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_types', function (Blueprint $table) {
            $table->id();
            $table->string('chat_type');
            $table->string('comment');
            $table->timestamps();
        });
        \DB::table('chat_types')->insert([     
            ['id'=> 1, 'chat_type' => 'SimpleChat', 'comment' => "This is a simple chat between two users"]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chat_types');
    }
}
