<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataAccountBank extends Model
{
    protected $table = 'data_account_bank';
    public $timestamps = true;
    protected $fillable = array('id', 'id_bank','account','branch','taxid','name', 'created_at', 'updated_at');
}
