<?php

namespace App\Models;

class AgentLink extends Model
{
    protected $table = 'megasio_play_api.agent_links';

    protected $fillable = [
        'agent_id',
        'name',
        'promotion_code',
        'status',
        'facebook_config',
        'kochava_config',
    ];

    protected $casts = [
        'facebook_config' => 'array',
        'kochava_config' => 'array',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPromotionCode($query, string $code)
    {
        return $query->where('promotion_code', $code);
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    public function scopeByAgentId($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }
}
