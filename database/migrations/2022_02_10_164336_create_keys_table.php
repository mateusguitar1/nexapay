<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('keys', function (Blueprint $table) {
            $table->integer('id',true);
            $table->string('authorization')->index('indx_authorization')->nullable();
            $table->string('authorization_withdraw_a4p')->index('indx_authorization_withdraw_a4p')->nullable();
            $table->text('url_callback')->index('indx_url_callback')->nullable();
            $table->text('url_callback_withdraw')->index('indx_url_callback_withdraw')->nullable();
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
        Schema::dropIfExists('keys');
    }
}
