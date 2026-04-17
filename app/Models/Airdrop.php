<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Airdrop extends Model
{
    protected $table = 'megasio_play_api.airdrops';

    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'create_rollover',
        'remark',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'amount' => 'decimal:8',
            'create_rollover' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
