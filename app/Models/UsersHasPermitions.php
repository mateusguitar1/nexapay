<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersHasPermitions extends Model
{
    use HasFactory;

    protected $table = 'users_has_permitions';
    public $timestamps = true;
    protected $fillable = array('id', 'user_id', 'permition_id', 'created_at', 'updated_at');
}
