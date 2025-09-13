<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Api extends Model
{
    //
    use SoftDeletes;
    protected $table = 'api_logs';
    public $timestamps = true;
    protected $fillable = array('id', 'order_id', 'method_payment', 'action', 'request_body', 'response_body', 'callback_body', 'http_status', 'created_at', 'updated_at');
}
