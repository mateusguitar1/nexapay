<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Keys extends Model
{

    protected $table = 'keys';
    public $timestamps = true;
    protected $fillable = array('id', 'authorization', 'authorization_withdraw_a4p', 'url_callback', 'url_callback_withdraw');


    public function client()
    {
        return $this->hasOne('App\Models\Clients','key_id');
    }
}
