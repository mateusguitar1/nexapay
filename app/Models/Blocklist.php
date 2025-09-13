<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blocklist extends Model
{
    use HasFactory;

    protected $table = 'block_list';
    public $timestamps = true;
    protected $fillable = array('id','client_id','user_id','cpf','highlight','blocked','created_at');


    public function client()
    {
        return $this->belongsTo('App\Models\Clients');
    }
}
