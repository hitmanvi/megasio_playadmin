<?php

namespace App\Models;

class Agent extends Model
{
    protected $table = 'megasio_play_api.agents';

    protected $fillable = [
        'name',
        'account',
        'password',
        'remark',
        'two_factor_secret',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    public function scopeByAccount($query, string $account)
    {
        return $query->where('account', 'like', "%{$account}%");
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    public function agentLinks()
    {
        return $this->hasMany(AgentLink::class, 'agent_id');
    }
}
