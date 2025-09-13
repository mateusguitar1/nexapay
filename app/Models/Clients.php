<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clients extends Model
{

    protected $table = 'clients';
    public $timestamps = true;
    protected $fillable = array('id','name','address','contact','bank_name','agency','type_account','number_account',
    'holder','document_holder','country','contract','days_expired_boleto','days_expired_pix','bank_invoice',
    'bank_pix','bank_ted','bank_cc','method_pix','days_safe_boleto','days_safe_pix','days_safe_ted','days_safe_cc','tax_id','key_id','withdraw_permition','bank_withdraw_permition'
    );

    public function tax(){
        return $this->belongsTo('App\Models\Taxes');
    }

    public function key(){
        return $this->belongsTo('App\Models\Keys');
    }

    public function bankPix(){
        return $this->belongsTo('App\Models\Banks', 'bank_pix');
    }

    public function bankInvoice(){
        return $this->belongsTo('App\Models\Banks', 'bank_invoice');
    }

    public function bankWithdrawPix(){
        return $this->belongsTo('App\Models\Banks', 'bank_withdraw_permition');
    }

    public function bankTed(){
        return $this->belongsTo('App\Models\Banks', 'bank_ted');
    }

    public function bankCC(){
        return $this->belongsTo('App\Models\Banks', 'bank_cc');
    }

}
