<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Whitelist extends Model
{
    use HasFactory;

    protected $table = 'white_list';
    public $timestamps = true;
    protected $fillable = array('id','client_id','user_id','user_document','type_list','created_at');


    public function client()
    {
        return $this->belongsTo('App\Models\Clients');
    }
}
