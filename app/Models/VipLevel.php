<?php

namespace App\Models;

class VipLevel extends Model
{
    protected $table = 'megasio_play_api.vip_levels';

    protected $fillable = [
        'level',
        'name',
        'icon',
        'required_exp',
        'description',
        'benefits',
        'sort_id',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'required_exp' => 'integer',
            'benefits' => 'array',
            'sort_id' => 'integer',
            'enabled' => 'boolean',
        ];
    }

    /**
     * Scope to filter by enabled status.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to filter by level.
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope to order by sort_id.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_id', 'asc');
    }
}
