<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class VipLevelGroup extends Model
{
    protected $table = 'megasio_play_api.vip_level_groups';

    protected $fillable = [
        'name',
        'icon',
        'card_img',
        'sort_id',
        'enabled',
    ];

    protected $casts = [
        'sort_id' => 'integer',
        'enabled' => 'boolean',
    ];

    /**
     * Get the VIP levels in this group.
     */
    public function vipLevels(): HasMany
    {
        return $this->hasMany(VipLevel::class, 'group_id');
    }

    /**
     * Scope to filter by enabled status.
     */
    public function scopeByEnabled($query, $enabled)
    {
        return $query->where('enabled', $enabled);
    }

    /**
     * Scope to filter enabled groups.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to filter disabled groups.
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
        return $query->orderBy('sort_id', 'asc')->orderBy('id', 'asc');
    }

    /**
     * Scope to filter by name.
     */
    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }
}
