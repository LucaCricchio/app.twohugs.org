<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHugsTable extends Migration
{
    /**
     * Run the migrations.
     * @table hugs
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hugs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->dateTime('created_at');
            $table->integer('search_id');
            $table->integer('user_seeker_id');
            $table->integer('user_sought_id');
            $table->dateTime('user_seeker_last_checkin')->nullable()->default(null);
            $table->dateTime('user_sought_last_checkin')->nullable()->default(null);
            $table->dateTime('closed_at')->nullable()->default(null);
            $table->integer('closed_by')->nullable()->default(null);
            $table->string('code', 50);
            $table->dateTime('user_seeker_who_are_you_request')->nullable()->default(null);
            $table->dateTime('user_sought_who_are_you_request')->nullable()->default(null);
            $table->integer('timed_out_user_id')->nullable()->default(null);

            $table->unique(["code"], 'unique_hugs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down()
     {
       Schema::dropIfExists('hugs');
     }
}
