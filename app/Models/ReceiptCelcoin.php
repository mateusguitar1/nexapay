<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptCelcoin extends Model
{
    protected $table = 'receipt_celcoin';
    public $timestamps = true;
    protected $fillable = array('id', 'transaction_id','receipt','created_at', 'updated_at');
}
