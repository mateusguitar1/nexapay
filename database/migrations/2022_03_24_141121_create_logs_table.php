<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->integer('id',true);
            $table->integer('user_id')->index('fk_historys_logs_users1')->nullable();
            $table->integer('client_id')->index('fk_historys_logs_clients1')->nullable();
            $table->string('action')->nullable();
            $table->string('type')->nullable();
            $table->string('ip')->nullable();
            $table->string('so')->nullable();
            $table->string('browser')->nullable();
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
        Schema::dropIfExists('logs');
    }
}
