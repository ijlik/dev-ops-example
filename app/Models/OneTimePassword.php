<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OneTimePassword extends Model
{
    use HasUuid;

    protected $fillable = [
        'data',
        'type',
        'code',
        'verified_at',
    ];

    protected $dates = [
        'verified_at'
    ];

    const TYPE_EMAIL = 'email';
    const TYPE_SMS = 'sms';
    const TYPE_WHATSAPP = 'whatsapp';
}
