<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    //
    protected $table = 'webhook';
    public $timestamps = true;
    protected $fillable = array('id','client_id','order_id','body','type_register');
}
