<?php

namespace App\Models;

class Currency extends Model
{
    protected $table = 'megasio_play_api.currencies';

    protected $fillable = [
        'code',
        'symbol',
        'icon',
        'sort_id',
    ];

    protected function casts(): array
    {
        return [
            'sort_id' => 'integer',
        ];
    }

    /**
     * Scope to filter by code.
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope to order by sort_id.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_id');
    }
}

