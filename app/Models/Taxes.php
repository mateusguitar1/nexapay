<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Taxes extends Model
{

    protected $table = 'taxes';
    public $timestamps = true;
    protected $fillable = ['id','boleto_absolute','boleto_percent','boleto_cancel','pix_absolute','pix_percent',
    'withdraw_absolute','withdraw_percent','remittance_absolute','remittance_percent','replacement_absolute',
    'replacement_percent','min_boleto','min_withdraw','min_replacement','min_pix','max_withdraw','max_replacement',
    'max_remittance','max_boleto','max_pix','min_fee_boleto','min_fee_withdraw','min_fee_remittance','min_fee_replacement',
    'min_fee_pix','max_boleto_vip','max_pix_vip','min_deposit','max_deposit','cc_percent','cc_absolute',
    'min_fee_cc','min_cc','max_cc','max_fee_withdraw','created_at','updated_at'];
}
