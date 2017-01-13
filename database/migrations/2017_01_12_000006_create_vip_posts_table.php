<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVipPostsTable extends Migration
{
    /**
     * Run the migrations.
     * @table vip_posts
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vip_posts', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('vip_id');
            $table->string('title', 255)->nullable()->default(null);
            $table->text('content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down()
     {
       Schema::dropIfExists('vip_posts');
     }
}
