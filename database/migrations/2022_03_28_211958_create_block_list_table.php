<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBlockListTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('block_list', function (Blueprint $table) {
            $table->integer('id',true);
            $table->integer('client_id')->index('index_block_list_clients1')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('cpf')->nullable();
            $table->boolean('highlight')->nullable();
            $table->boolean('blocked')->nullable();
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
        Schema::dropIfExists('block_list');
    }
}
