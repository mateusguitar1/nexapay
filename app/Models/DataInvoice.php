<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataInvoice extends Model
{
    //
    protected $table = 'data_invoice';
    public $timestamps = true;
    protected $fillable = array('id', 'transaction_id', 'client_id', 'order_id', 'barcode', 'done_at', 'created_at', 'updated_at');
}
