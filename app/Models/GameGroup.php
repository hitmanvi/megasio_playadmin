<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GameGroup extends Model
{
    protected $table = 'megasio_play_api.game_groups';

    protected $fillable = [
        'category',
        'sort_id',
        'app_limit',
        'web_limit',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'sort_id' => 'integer',
            'app_limit' => 'integer',
            'web_limit' => 'integer',
            'enabled' => 'boolean',
        ];
    }

    /**
     * Get the games for the game group.
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'megasio_play_api.game_group_game')
            ->withPivot('sort_id')
            ->withTimestamps()
            ->orderBy('game_group_game.sort_id');
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by enabled status.
     */
    public function scopeByEnabled($query, $enabled)
    {
        return $query->where('enabled', $enabled);
    }

    /**
     * Scope to filter by enabled game groups.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to filter by disabled game groups.
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
}

