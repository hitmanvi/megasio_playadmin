<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethodFieldConfig extends Model
{
    protected $table = 'megasio_play_api.payment_method_field_configs';
    protected $fillable = [
        'name',
        'deposit_fields',
        'withdraw_fields',
    ];

    protected $casts = [
        'deposit_fields' => 'array',
        'withdraw_fields' => 'array',
    ];

}
