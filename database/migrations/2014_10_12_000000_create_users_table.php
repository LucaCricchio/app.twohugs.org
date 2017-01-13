<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     * @table users
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('username', 50)->nullable()->default(null);
            $table->string('first_name', 50)->nullable()->default(null);
            $table->string('last_name', 50)->nullable()->default(null);
            $table->string('email', 100)->unique();
            $table->string('password', 255)->nullable()->default(null);
            $table->date('birth_date')->nullable()->default(null);
            $table->char('gender', 1)->nullable()->default(null);
            $table->string('telephone', 50)->nullable()->default(null);
            $table->string('avatar', 255)->nullable()->default(null);
            $table->string('facebook_user_id', 100)->nullable()->default(null);
            $table->string('google_user_id', 100)->nullable()->default(null);
            $table->string('activation_code', 100)->nullable()->default(null)->unique();
            $table->dateTime('activation_date')->nullable()->default(null);
            $table->dateTime('last_login')->nullable()->default(null);
            $table->integer('country')->nullable()->default(null);
            $table->string('city', 50)->nullable()->default(null);
            $table->string('address', 100)->nullable()->default(null);
            $table->string('zipcode', 10)->nullable()->default(null);
            $table->tinyInteger('status')->nullable()->default(null);
            $table->decimal('geo_latitude', 10, 7)->nullable()->default(null);
            $table->decimal('geo_longitude', 10, 7)->nullable()->default(null);
            $table->dateTime('geo_last_update')->nullable()->default(null);
            $table->tinyInteger('blocked')->nullable()->default('0');
            $table->tinyInteger('completed')->nullable()->default('0');
            $table->string('gcm_device_id', 255)->nullable()->default(null);
            $table->integer('max_duration')->default('0');
            $table->integer('max_distance')->default('0');
            $table->string('parent_email', 100)->nullable()->default(null)->unique();

            $table->nullableTimestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down()
     {
       Schema::dropIfExists('users');
     }
}
