<?php

namespace App\Models;

class User extends Model
{
    protected $table = 'megasio_play_api.users';

    protected $fillable = [
        'email',
        'phone',
    ];
}

