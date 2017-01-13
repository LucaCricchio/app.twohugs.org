<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSearchesTable extends Migration
{
    /**
     * Run the migrations.
     * @table searches
     *
     * @return void
     */
    public function up()
    {
        Schema::create('searches', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id');
            $table->dateTime('created_at');
            $table->dateTime('keep_alive')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->tinyInteger('stopped')->nullable()->default(null);
            $table->integer('max_duration')->unsigned()->default('0');
            $table->integer('max_distance')->default('0');
            $table->dateTime('finished_at')->nullable()->default(null);
            $table->tinyInteger('success')->default('0');
            $table->tinyInteger('timeout')->default('0');
            $table->decimal('geo_latitude', 10, 7);
            $table->decimal('geo_longitude', 10, 7);
            $table->string('ip', 15);
            $table->text('extra')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down()
     {
       Schema::dropIfExists('searches');
     }
}
