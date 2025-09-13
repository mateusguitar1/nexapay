<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankISPB extends Model
{
    use HasFactory;

    protected $table = 'bank_ispb';
    public $timestamps = true;
    protected $fillable = array('id','ispb','compe','name','created_at','updated_at');
}
