[<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSearchListUsersTable extends Migration
{
    /**
     * Run the migrations.
     * @table search_list_users
     *
     * @return void
     */
    public function up()
    {
        Schema::create('search_list_users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('search_list_id');
            $table->integer('user_id');
            $table->integer('order')->nullable()->default(null);
            $table->dateTime('fetched_at')->nullable()->default(null);
            $table->dateTime('responsed_at')->nullable()->default(null);
            $table->tinyInteger('response_type')->nullable()->default(null);
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down()
     {
       Schema::dropIfExists('search_list_users');
     }
}
