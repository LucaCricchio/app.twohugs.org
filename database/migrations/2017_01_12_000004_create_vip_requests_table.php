<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVipRequestsTable extends Migration
{
    /**
     * Run the migrations.
     * @table vip_requests
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vip_requests', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id');
            $table->smallInteger('year');
            $table->tinyInteger('month');
            $table->dateTime('fetched_at')->nullable()->default(null);
            $table->dateTime('responsed_at')->nullable()->default(null);
            $table->tinyInteger('response_type')->default('0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down()
     {
       Schema::dropIfExists('vip_requests');
     }
}
