<?php

namespace App\Models;

class Agent extends Model
{
    protected $table = 'megasio_play_api.agents';

    protected $fillable = [
        'name',
        'promotion_code',
        'account',
        'password',
        'remark',
        'parent_id',
        'facebook_config',
        'kochava_config',
        'two_factor_secret',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'facebook_config' => 'array',
        'kochava_config' => 'array',
        'password' => 'hashed',
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
     * Scope to filter by account (partial match).
     */
    public function scopeByAccount($query, string $account)
    {
        return $query->where('account', 'like', "%{$account}%");
    }

    /**
     * Scope to filter by parent_id.
     */
    public function scopeByParentId($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
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

    /**
     * Parent agent (optional).
     */
    public function parent()
    {
        return $this->belongsTo(Agent::class, 'parent_id');
    }

    /**
     * Child agents.
     */
    public function children()
    {
        return $this->hasMany(Agent::class, 'parent_id');
    }
}
