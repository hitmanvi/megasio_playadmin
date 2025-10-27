<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameGroupGame extends Model
{
    protected $table = 'megasio_play_api.game_group_game';

    protected $fillable = [
        'game_group_id',
        'game_id',
        'sort_id',
    ];

    protected function casts(): array
    {
        return [
            'game_group_id' => 'integer',
            'game_id' => 'integer',
            'sort_id' => 'integer',
        ];
    }

    /**
     * Get the game group that owns the pivot.
     */
    public function gameGroup(): BelongsTo
    {
        return $this->belongsTo(GameGroup::class);
    }

    /**
     * Get the game that owns the pivot.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Scope to order by sort_id.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_id');
    }
}

