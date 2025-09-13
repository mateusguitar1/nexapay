<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transactions extends Model
{
    protected $table = 'transactions_detail';
    public $timestamps = true;
    protected $fillable = array('id','solicitation_date','paid_date','cancel_date','refund_date','freeze_date','chargeback_date',
    'final_date','disponibilization_date','due_date','code','client_id','order_id','user_id','user_account_data','user_name',
    'user_document','code_bank','id_bank','bank_data','type_transaction','method_transaction','amount_solicitation',
    'final_amount','percent_fee','fixed_fee','min_fee','comission','status','receipt','observation','confirmation_callback',
    'payment_id','confirmed_bank','provider_reference');

    public function bank(){
        return $this->belongsTo('App\Models\Banks','id_bank');
    }

    public function client(){
        return $this->belongsTo('App\Models\Clients');
    }

    public function dataInvoice(){
        return $this->belongsTo('App\Models\DataInvoice','data_invoice_id');
    }

    public function getUser(){
        return $this->HasOne('App\Models\User','id','user_id');
    }

    public function scopeGetClientID($query, $clientId){
        if( $clientId != 'all' ){
            $query->where('client_id', $clientId);
        }
        return $query;
    }

    public function scopeGetType($query, $type){
        if( !empty($type) ){
            if( $type[0] === 'all' ){
                $query->where('type_transaction','=','deposit')->orwhere('type_transaction','=','withdraw');
            } else {
                foreach( $type as $key => $types ){
                    if( $key === 0  ){
                        $query->where('type_transaction', $types);
                    } else {
                        $query->orwhere('type_transaction', $types);
                    }
                }
            }
        }
        return $query;
    }

    public function scopeGetMethod($query, $method){
        if( !empty($method) ){
            if( in_array('all', $method) ){
                $method = ['invoice','automatic_checking','credit_card','ame_digital','debit_card','bank_transfer','TEF'];
            }

            foreach( $method as $key => $methods ){
                if( $key === 0  ){
                    $query->where('method_transaction', $methods);
                } else {
                    $query->orwhere('method_transaction', $methods);
                }
            }
            return $query;
        }
    }

    public function scopeGetBanks($query, $banks){
        if( !empty($banks) ){
            if( in_array('all', $banks) ){
                $banks = Model('Banks')::all();
            }

            foreach( $banks as $key => $bankss ){
                if( $key === 0  ){
                    $query->where('id_bank', $bankss);
                } else {
                    $query->orwhere('id_bank', $bankss);
                }
            }
        }
        return $query;
    }

    public function scopeGetStatus($query, $status=''){
        if( !empty($status) ){
            if( in_array('all', $status) ){
                $status = ['pending','confirmed','canceled','refund','chargeback','freeze'];
            }
            foreach( $status as $key => $statuss ){
                if( $key === 0  ){
                    $query->where('status', $statuss);
                } else {
                    $query->orwhere('status', $statuss);
                }
            }
        }
        return $query;
    }

    public function scopeGetSearch($query, $search=''){
        if( !empty($search) ){
            $query->where('order_id', 'like', '%'.$search.'%');
            $query->orWhere('user_id', 'like', '%'.$search.'%');
            $query->orWhere('code', 'like', '%'.$search.'%');
        }
        return $query;
    }
}
