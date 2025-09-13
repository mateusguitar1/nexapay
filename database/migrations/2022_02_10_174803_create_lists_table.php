<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lists', function (Blueprint $table) {
            $table->integer('id',true);
            $table->integer("client_id")->index('fk_white_list_clients1')->nullable();
            $table->string("user_id")->nullable();
            $table->string("user_document")->nullable();
            $table->string("added_by",255)->default("system")->nullable();
            $table->string("type_list",20)->default("whitelist")->nullable();
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
        Schema::dropIfExists('lists');
    }
}
