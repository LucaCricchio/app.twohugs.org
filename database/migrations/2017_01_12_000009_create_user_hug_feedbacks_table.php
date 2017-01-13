<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserHugFeedbacksTable extends Migration
{
    /**
     * Run the migrations.
     * @table user_hug_feedbacks
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_hug_feedbacks', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('hug_id');
            $table->dateTime('created_at');
            $table->tinyInteger('result')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down()
     {
       Schema::dropIfExists('user_hug_feedbacks');
     }
}
