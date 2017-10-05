<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddImageAndLinkFieldsToVipPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vip_posts', function (Blueprint $table) {
            $table->string('image_path')->nullable();
            $table->string('video_link')->nullable();
            $table->string('content')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vip_posts', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'video_link']);
            $table->string('content')->nullable(false)->change();
        });
    }
}
