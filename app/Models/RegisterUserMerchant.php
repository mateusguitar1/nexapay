<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegisterUserMerchant extends Model
{
    use HasFactory;

    protected $table = 'register_user_merchant';
    public $timestamps = true;
    protected $fillable = [
        'id',
        'customer_id',
        'name',
        'email',
        'phone',
        'mobilePhone',
        'cpfCnpj',
        'postalCode',
        'address',
        'addressNumber',
        'complement',
        'province',
        'externalReference',
        'notificationDisabled',
        'additionalEmails',
        'municipalInscription',
        'stateInscription',
        'observations'
    ];
}
