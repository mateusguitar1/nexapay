<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDataAccountBankTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_account_bank', function (Blueprint $table) {
            $table->integer("id",true);
            $table->integer("id_bank");
            $table->text("account")->nullable();
            $table->text("branch")->nullable();
            $table->text("taxid")->nullable();
            $table->text("name")->nullable();
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
        Schema::dropIfExists('data_account_bank');
    }
}
