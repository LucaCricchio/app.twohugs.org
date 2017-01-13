<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserHugSelfiesTable extends Migration
{
    /**
     * Run the migrations.
     * @table user_hug_selfies
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_hug_selfies', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('hug_id');
            $table->integer('user_id');
            $table->dateTime('created_at');
            $table->string('file_path', 512);
            $table->string('file_name', 255);
            $table->integer('file_size');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down()
     {
       Schema::dropIfExists('user_hug_selfies');
     }
}
