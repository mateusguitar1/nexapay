<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhiteListTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('white_list', function (Blueprint $table) {
            $table->integer('id',true);
            $table->integer("client_id")->index('index_white_list_clients1')->nullable();
            $table->string("user_id")->nullable();
            $table->string("user_document")->nullable();
            $table->string("type_list",6)->default("system")->nullable();
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
        Schema::dropIfExists('white_list');
    }
}
