<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->integer('id',true);
            $table->double('boleto_absolute',24,2)->nullable();
            $table->double('boleto_percent',24,2)->nullable();
            $table->double('boleto_cancel',24,2)->nullable();

            $table->double('pix_absolute',24,2)->nullable();
            $table->double('pix_percent',24,2)->nullable();

            $table->double('withdraw_absolute',24,2)->nullable();
            $table->double('withdraw_percent',24,2)->nullable();
            $table->double('remittance_absolute',24,2)->nullable();
            $table->double('remittance_percent',24,2)->nullable();
            $table->double('replacement_absolute',24,2)->nullable();
            $table->double('replacement_percent',24,2)->nullable();

            // Min Amount
            $table->double('min_boleto',24,2)->nullable();
            $table->double('min_withdraw',24,2)->nullable();
            $table->double('min_replacement',24,2)->nullable();
            $table->double('min_remittance',24,2)->nullable();
            $table->double('min_pix',24,2)->nullable();

            // Max Amount
            $table->double('max_withdraw',24,2)->nullable();
            $table->double('max_replacement',24,2)->nullable();
            $table->double('max_remittance',24,2)->nullable();
            $table->double('max_boleto',24,2)->nullable();
            $table->double('max_pix',24,2)->nullable();

            // Min Fee
            $table->double('min_fee_boleto',24,2)->nullable();
            $table->double('min_fee_withdraw',24,2)->nullable();
            $table->double('min_fee_remittance',24,2)->nullable();
            $table->double('min_fee_replacement',24,2)->nullable();
            $table->double('min_fee_pix',24,2)->nullable();

            // Base Fee
            // $table->double('base_fee_others',24,2)->nullable();
            // $table->double('base_fee_others_apply',24,2)->nullable();

            // Insurance
            $table->double('max_boleto_vip',24,2)->nullable();
            $table->double('max_pix_vip',24,2)->nullable();

            $table->double('max_fee_withdraw',24,2)->nullable();
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
        Schema::dropIfExists('taxes');
    }
}
