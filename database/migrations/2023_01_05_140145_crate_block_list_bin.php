<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrateBlockListBin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('block_list_bin', function (Blueprint $table) {
            $table->integer("id",true)->index("fk_block_list_bin_id");
            $table->integer("client_id")->index("fk_block_list_bin_client_id");
            $table->integer("user_id")->index("fk_block_list_bin_user_id");
            $table->string("card_bin")->index("fk_block_list_bin_card_bin");
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
        //
        Schema::dropIfExists('block_list_bin');
    }
}
