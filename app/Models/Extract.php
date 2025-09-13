<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Extract extends Model
{
    //
    use SoftDeletes;
    protected $table = 'extract';
    public $timestamps = true;
    protected $fillable = [
        'transaction_id',
        'order_id',
        'client_id',
        'user_id',
        'bank_id',
        'type_transaction_extract',
        'description_code',
        'description_text',
        'cash_flow',
        'final_amount',
        'quote',
        'quote_markup',
        'receita',
        'disponibilization_date',
        'created_at',
        'updated_at',
    ];

    public function client(){
        return $this->belongsTo('App\Models\Clients','client_id');
    }

    public function transaction(){
        return $this->belongsTo('App\Models\Transactions','transaction_id');
    }

    public function bank(){
        return $this->belongsTo('App\Models\Banks','bank_id');
    }
}
