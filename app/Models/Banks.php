<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Banks extends Model
{
    protected $table = 'banks';
    public $timestamps = true;
    protected $fillable = [
        'id',
        'name',
        'code',
        'holder',
        'agency',
        'type_account',
        'account',
        'document',
        'status',
        'address',
        'username_bs2',
        'password_bs2',
        'client_id_bs2',
        'client_secret_bs2',
        'token_bs2',
        'refresh_token_bs2',
        'withdraw_permition',
        'bank_withdraw_permition',
        'prefix',
        'paghiper_api',
        'auth_openpix',
        'acess_token_asaas',
        'client_id_suitpay',
        'client_secret_suitpay',
        'shipay_client_id',
        'shipay_access_key',
        'shipay_secret_key',
        'token_shipay',
    ];


    public function clients_invoice()
    {
        return $this->hasMany('App\Models\Clients','bank_invoice');
    }

    // public function clients_bb()
    // {
    //     return $this->hasMany('App\Models\Clients','bank_automatic_checking_bb');
    // }

    // public function clients_bradesco()
    // {
    //     return $this->hasMany('App\Models\Clients','bank_automatic_checking_bradesco');
    // }

    // public function clients_caixa()
    // {
    //     return $this->hasMany('App\Models\Clients','bank_automatic_checking_caixa');
    // }

    // public function clients_itau()
    // {
    //     return $this->hasMany('App\Models\Clients','bank_automatic_checking_itau');
    // }

    // public function clients_santander()
    // {
    //     return $this->hasMany('App\Models\Clients','bank_automatic_checking_santander');
    // }

    // public function clients_card()
    // {
    //     return $this->hasMany('App\Models\Clients','bank_credit_card');
    // }

    // public function clients_ame()
    // {
    //     return $this->hasMany('App\Models\Clients','bank_ame');
    // }

    public function clients_pix()
    {
        return $this->hasMany('App\Models\Clients','bank_pix');
    }

}
