<?php

namespace App\Models;

class Blacklist extends Model
{
    protected $table = 'megasio_play_api.blacklists';

    protected $fillable = [
        'value',
        'reason',
        'hit_count',
    ];

    protected $casts = [
        'hit_count' => 'integer',
    ];

    /**
     * Scope: 按值筛选
     */
    public function scopeByValue($query, string $value)
    {
        return $query->where('value', 'like', '%' . $value . '%');
    }
}
