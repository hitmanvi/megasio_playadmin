<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPaymentExtraInfo extends Model
{
    protected $table = 'megasio_play_api.user_payment_extra_infos';

    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_WITHDRAW = 'withdraw';

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'data',
        'duplicate_across_user',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'data' => 'array',
            'duplicate_across_user' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
