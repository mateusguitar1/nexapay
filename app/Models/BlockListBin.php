<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockListBin extends Model
{
    //
    protected $table = "block_list_bin";
    public $timestamps = true;
    protected $fillable = ['client_id','user_id','card_bin','created_at','updated_at'];
    protected $hidden = ['id'];
}
