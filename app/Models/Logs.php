<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logs extends Model
{
    use HasFactory;

    protected $table = 'logs';
    public $timestamps = true;
    protected $fillable = array('id','user_id','client_id','type','action','created_at','ip','browser','so');

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public function client(){
        return $this->belongsTo('App\Models\Clients');
    }
}
