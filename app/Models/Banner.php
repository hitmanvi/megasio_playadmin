<?php

namespace App\Models;

class Banner extends Model
{
    protected $table = 'megasio_play_api.banners';

    protected $fillable = [
        'type',
        'web_img_url',
        'app_img_url',
        'web_rule_url',
        'app_rule_url',
        'enabled',
        'sort_id',
        'started_at',
        'ended_at',
        'description',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'sort_id' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Scope to filter by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by enabled status.
     */
    public function scopeByEnabled($query, $enabled)
    {
        return $query->where('enabled', $enabled);
    }

    /**
     * Scope to filter by enabled banners.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to filter by disabled banners.
     */
    public function scopeDisabled($query)
    {
        return $query->where('enabled', false);
    }

    /**
     * Scope to order by sort_id.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_id');
    }

    /**
     * Scope to filter banners within date range.
     */
    public function scopeActiveAt($query, $date = null)
    {
        $date = $date ?? now();
        
        return $query->where(function ($q) use ($date) {
            $q->whereNull('started_at')
              ->orWhere('started_at', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('ended_at')
              ->orWhere('ended_at', '>=', $date);
        });
    }
}

