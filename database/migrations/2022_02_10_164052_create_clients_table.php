<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->integer('id',true);
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('contact')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('agency')->nullable();
            $table->string('type_account')->nullable();
            $table->string('number_account')->nullable();
            $table->string('holder')->nullable();
            $table->string('document_holder')->nullable();
            $table->string('country')->nullable();
            $table->string('contract')->nullable();
            $table->integer('days_expired_boleto')->nullable();
            $table->integer('days_expired_pix')->nullable();

            $table->integer('bank_invoice')->index('fk_clients_banks_invoice1')->nullable();
            $table->integer('bank_pix')->index('fk_clients_banks_pix1')->nullable();
            $table->text('method_pix')->index('fk_clients_method_pix1')->default("dinamico");

            $table->integer('days_safe_boleto')->nullable();
            $table->integer('days_safe_pix')->nullable();
            $table->integer('tax_id')->index('fk_clients_tax1')->nullable();
            $table->integer('key_id')->index('fk_clients_keys1')->nullable();

            $table->boolean('withdraw_permition')->default(false)->nullable();
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
        Schema::dropIfExists('clients');
    }
}
