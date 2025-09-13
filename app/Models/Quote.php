<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    //
    protected $table = 'quote';
    public $timestamps = true;
    protected $fillable = array('id', 'quote', 'created_at', 'updated_at');
}
