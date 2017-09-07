<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditVipRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('vip_requests', function (Blueprint $table) {
            $table->dropColumn(['year', 'month']);
            $table->integer('positive_feedbacks');
            $table->integer('potential_users_list_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vip_requests', function (Blueprint $table) {
            $table->smallInteger('year');
            $table->tinyInteger('month');
            $table->dropColumn(['positive_feedbacks', 'potential_users_list_id']);
        });
    }
}
