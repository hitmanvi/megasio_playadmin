<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $table = 'megasio_play_api.tags';

    protected $fillable = [
        'name',
        'display_name',
        'color',
        'description',
        'enabled',
        'sort_id',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'sort_id' => 'integer',
    ];

    /**
     * Get the users that have this tag.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'megasio_play_api.user_tags')
            ->withTimestamps();
    }

    /**
     * Scope: 按启用状态筛选
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope: 按名称筛选
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', '%' . $name . '%');
    }
}
