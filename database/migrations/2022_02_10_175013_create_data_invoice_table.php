<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDataInvoiceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('data_invoice', function (Blueprint $table) {
            $table->integer('id',true);
            $table->integer('transaction_id')->index('indx_transaction_id_data_invoice')->nullable();
            $table->integer('client_id')->index('indx_client_id_data_invoice')->nullable();
            $table->integer('order_id')->index('indx_order_id_data_invoice')->nullable();
            $table->string('barcode')->nullable();
            $table->dateTime('date_time')->nullable();
            $table->dateTime('done_at')->nullable();
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
        Schema::dropIfExists('data_invoice');
    }
}
