<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExtractTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('extract', function (Blueprint $table) {
            $table->integer("id",true);
            $table->integer("transaction_id");
            $table->string("order_id",255)->nullable();
            $table->integer("client_id");
            $table->string("user_id",255)->nullable();
            $table->integer("bank_id")->nullable();
            $table->string("type_transaction_extract",255)->nullable();
            $table->string("description_code",255)->nullable();
            $table->text("description_text")->nullable();
            $table->double("amount")->default('0.00')->nullable();
            $table->double("receita")->default('0.00')->nullable();
            $table->timestamp('disponibilization_date')->nullable();
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
        Schema::dropIfExists('extract');
    }
}
