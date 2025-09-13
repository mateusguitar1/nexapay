<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions_detail', function (Blueprint $table) {
            $table->integer('id',true);
            $table->dateTime('solicitation_date')->index('fk_transactions_solicitation_date1')->nullable();
            $table->dateTime('paid_date')->index('fk_transactions_paid_date1')->nullable();
            $table->dateTime('cancel_date')->index('fk_transactions_cancel_date1')->nullable();
            $table->dateTime('refund_date')->index('fk_transactions_refund_date1')->nullable();
            $table->dateTime('freeze_date')->index('fk_transactions_freeze_date1')->nullable();
            $table->dateTime('chargeback_date')->index('fk_transactions_chargeback_date1')->nullable();
            $table->dateTime('final_date')->index('fk_transactions_final_date1')->nullable();
            $table->dateTime('disponibilization_date')->index('fk_transactions_disponibilization_date1')->nullable();
            $table->dateTime('due_date')->index('fk_transactions_due_date1')->nullable();
            $table->string('code')->index('fk_transactions_code1')->nullable();
            $table->integer('client_id')->index('fk_transactions_clients1')->nullable();
            $table->text('order_id')->index('fk_transactions_order_id1')->nullable();
            $table->text('user_id')->nullable();
            $table->text('user_account_data')->nullable();
            $table->text('user_document')->index('fk_transactions_user_document1')->nullable();

            $table->string('code_bank')->nullable();
            $table->integer('id_bank')->index('fk_transactions_banks1')->nullable();
            $table->string('bank_data')->nullable();
            $table->string('type_transaction')->index('fk_transactions_type_transaction1')->nullable();
            $table->string('method_transaction')->index('fk_transactions_method_transaction1')->nullable();
            $table->double('amount_solicitation',24,2)->nullable();
            $table->double('final_amount',24,2)->nullable();
            $table->double('percent_fee',24,2)->nullable();
            $table->double('fixed_fee',24,2)->nullable();
            $table->double('min_fee',24,2)->nullable();
            $table->double('comission',24,2)->nullable();
            $table->string('status')->nullable();
            $table->string('receipt')->nullable();
            $table->string('observation')->nullable();
            $table->integer('confirmation_callback')->nullable();
            $table->text('payment_id')->nullable();

            $table->string('link_callback_bank')->nullable();
            $table->string('deep_link')->nullable();
            $table->string('canceled_manual')->nullable();
            $table->string('url_retorna')->nullable();
            $table->integer('confirmed_bank')->nullable();
            $table->integer('credit_card_refunded')->nullable();
            $table->integer('confirm_callback_refund')->nullable();
            $table->text('response_refund_client')->nullable();
            $table->integer('data_invoice_id')->index('fk_transactions_datainvoice1')->nullable();
            $table->text('hash_btc')->nullable();
            $table->text('base64_image')->nullable();
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
        Schema::dropIfExists('transactions_detail');
    }
}
