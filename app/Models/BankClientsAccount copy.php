<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BankClientsAccount extends Model
{
    //
    protected $table = 'bank_clients_account';
    public $timestamps = true;
    protected $filable = array('code','name');

}
