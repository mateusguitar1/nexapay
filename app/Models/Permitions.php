<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permitions extends Model
{
    protected $table = 'permitions';
    public $timestamps = true;
    protected $fillable = array('id', 'title', 'created_at', 'updated_at');
}
