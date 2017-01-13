<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserSearchTimeoutsTable extends Migration
{
    /**
     * Run the migrations.
     * @table user_search_timeouts
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_search_timeouts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('search_id');
            $table->integer('user_id');
            $table->dateTime('timed_out_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down()
     {
       Schema::dropIfExists('user_search_timeouts');
     }
}
