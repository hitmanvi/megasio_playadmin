<?php

namespace App\Models;

class Agent extends Model
{
    protected $table = 'megasio_play_api.agents';

    protected $fillable = [
        'name',
        'promotion_code',
        'facebook_pixel_id',
        'facebook_access_token',
        'kochava_app_id',
        'status',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by promotion_code.
     */
    public function scopeByPromotionCode($query, string $code)
    {
        return $query->where('promotion_code', $code);
    }

    /**
     * Scope to filter by name (partial match).
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /**
     * Scope to filter active agents.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to filter inactive agents.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }
}
