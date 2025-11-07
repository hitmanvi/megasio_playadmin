<?php

namespace App\Models;

class User extends Model
{
    protected $table = 'megasio_play_api.users';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
        'ban_reason',
    ];

    /**
     * User status constants.
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_BANNED = 'banned';

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

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to filter banned users.
     */
    public function scopeBanned($query)
    {
        return $query->where('status', self::STATUS_BANNED);
    }

    /**
     * Check if user is banned.
     */
    public function isBanned(): bool
    {
        return $this->status === self::STATUS_BANNED;
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}

