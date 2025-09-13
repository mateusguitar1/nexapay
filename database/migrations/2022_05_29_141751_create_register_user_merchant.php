<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRegisterUserMerchant extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('register_user_merchant', function (Blueprint $table) {
            $table->integer("id",true);
            $table->string("customer_id")->nullable();
            $table->string("name")->index("indx_name_user_merchant")->nullable();
            $table->string("email")->index("indx_email_user_merchant")->nullable();
            $table->string("phone")->index("indx_phone_user_merchant")->nullable();
            $table->string("mobilePhone")->index("indx_mobilePhone_user_merchant")->nullable();
            $table->string("cpfCnpj")->index("indx_cpfCnpj_user_merchant")->nullable();
            $table->string("postalCode")->index("indx_postalCode_user_merchant")->nullable();
            $table->string("address")->index("indx_address_user_merchant")->nullable();
            $table->string("addressNumber")->index("indx_addressNumber_user_merchant")->nullable();
            $table->string("complement")->index("indx_complement_user_merchant")->nullable();
            $table->string("province")->index("indx_province_user_merchant")->nullable();
            $table->string("externalReference")->index("indx_externalReference_user_merchant")->nullable();
            $table->boolean("notificationDisabled")->index("indx_notificationDisabled_user_merchant")->default('1')->nullable();
            $table->string("additionalEmails")->index("indx_additionalEmails_user_merchant")->nullable();
            $table->string("municipalInscription")->index("indx_municipalInscription_user_merchant")->nullable();
            $table->string("stateInscription")->index("indx_stateInscription_user_merchant")->nullable();
            $table->text("observations")->index("indx_observations_user_merchant")->nullable();
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
        Schema::dropIfExists('register_user_merchant');
    }
}
