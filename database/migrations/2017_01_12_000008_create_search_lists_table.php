<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSearchListsTable extends Migration
{
    /**
     * Run the migrations.
     * @table search_lists
     *
     * @return void
     */
    public function up()
    {
        Schema::create('search_lists', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('search_id');
            $table->dateTime('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down()
     {
       Schema::dropIfExists('search_lists');
     }
}
