<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends Model
{
    protected $table = 'megasio_play_api.user_activities';

    protected $fillable = [
        'user_id',
        'activity_type',
        'description',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user that owns the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope to filter by user_id.
     */
    public function scopeByUserId($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by activity_type.
     */
    public function scopeByActivityType($query, $activityType)
    {
        return $query->where('activity_type', $activityType);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeByDateRange($query, $startDate = null, $endDate = null)
    {
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }
        return $query;
    }

    /**
     * Scope to filter by IP address.
     */
    public function scopeByIpAddress($query, $ipAddress)
    {
        return $query->where('ip_address', 'like', "%{$ipAddress}%");
    }

    /**
     * Scope to filter by user email or phone.
     */
    public function scopeByUserEmailOrPhone($query, $emailOrPhone)
    {
        return $query->whereHas('user', function ($q) use ($emailOrPhone) {
            $q->where('email', 'like', "%{$emailOrPhone}%")
              ->orWhere('phone', 'like', "%{$emailOrPhone}%");
        });
    }

    /**
     * Scope to filter by user uid.
     * Supports both user.uid field and user_id as fallback.
     */
    public function scopeByUid($query, $uid)
    {
        return $query->where(function ($q) use ($uid) {
            $q->whereHas('user', function ($subQ) use ($uid) {
                $subQ->where('uid', $uid);
            })->orWhere('user_id', $uid);
        });
    }
}

