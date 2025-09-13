<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAskTransactionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ask_transaction', function (Blueprint $table) {
            $table->integer('id',true);
            $table->text('description')->nullable();
            $table->integer('relation')->index('fk_ask_transaction_ask_transaction1')->nullable();
            $table->text('sendby')->nullable();
            $table->text('status')->nullable();
            $table->text('file')->nullable();
            $table->text('order_id')->nullable();
            $table->integer('client_id')->index('fk_ask_transaction_clients1')->nullable();
            $table->integer('user_id')->index('fk_ask_transaction_users1')->nullable();
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
        Schema::dropIfExists('ask_transaction');
    }
}
