<?php

namespace App\Models;

class User extends Model
{
    protected $table = 'megasio_play_api.users';

    protected $fillable = [
        'email',
        'phone',
    ];

    /**
     * Scope to filter by uid.
     */
    public function scopeByUid($query, $uid)
    {
        return $query->where('uid', $uid);
    }

    /**
     * Scope to filter by email.
     */
    public function scopeByEmail($query, $email)
    {
        return $query->where('email', 'like', "%{$email}%");
    }

    /**
     * Scope to filter by phone.
     */
    public function scopeByPhone($query, $phone)
    {
        return $query->where('phone', 'like', "%{$phone}%");
    }

    /**
     * Scope to filter by email or phone.
     */
    public function scopeByEmailOrPhone($query, $emailOrPhone)
    {
        return $query->where(function ($q) use ($emailOrPhone) {
            $q->where('email', 'like', "%{$emailOrPhone}%")
              ->orWhere('phone', 'like', "%{$emailOrPhone}%");
        });
    }
}

