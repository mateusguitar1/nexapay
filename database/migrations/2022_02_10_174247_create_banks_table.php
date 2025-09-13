<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->integer('id',true);
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->string('holder')->nullable();
            $table->string('agency')->nullable();
            $table->string('type_account')->nullable();
            $table->string('account')->nullable();
            $table->string('document')->nullable();
            $table->string('address')->nullable();
            $table->string('status')->nullable();
            $table->text('username_bs2')->nullable();
            $table->text('password_bs2')->nullable();
            $table->text('client_id_bs2')->nullable();
            $table->text('client_secret_bs2')->nullable();
            $table->text('token_bs2')->nullable();
            $table->text('refresh_token_bs2')->nullable();

            $table->boolean('withdraw_permition')->default(false)->nullable();
            $table->integer('bank_withdraw_permition')->nullable();
            $table->text('prefix')->nullable();
            $table->text('paghiper_api')->nullable();
            $table->text('auth_openpix')->nullable();
            $table->text('acess_token_asaas')->nullable();

            $table->text('client_id_celcoin')->nullable();
            $table->text('client_secret_celcoin')->nullable();
            $table->text('access_token_celcoin')->nullable();

            $table->text('pixkey')->nullable();

            $table->text('shipay_client_id')->nullable();
            $table->text('shipay_access_key')->nullable();
            $table->text('shipay_secret_key')->nullable();

            $table->text('shipay_method')->nullable();

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
        Schema::dropIfExists('banks');
    }
}
