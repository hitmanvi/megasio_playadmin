<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyCashback extends Model
{
    protected $table = 'megasio_play_api.weekly_cashbacks';
    /** 进行中 */
    const STATUS_ACTIVE = 'active';

    /** 待领取 */
    const STATUS_CLAIMABLE = 'claimable';

    /** 已领取 */
    const STATUS_CLAIMED = 'claimed';

    /** 已过期 */
    const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'period',
        'currency',
        'wager',
        'payout',
        'status',
        'rate',
        'amount',
        'claimed_at',
    ];

    protected $casts = [
        'period' => 'integer',
        'wager' => 'decimal:8',
        'payout' => 'decimal:8',
        'rate' => 'decimal:4',
        'amount' => 'decimal:8',
        'claimed_at' => 'datetime',
    ];

    protected $appends = [
        'period_start_at',
        'period_end_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPeriodStartAtAttribute(): ?CarbonImmutable
    {
        return $this->periodWeekStart();
    }

    public function getPeriodEndAtAttribute(): ?CarbonImmutable
    {
        $start = $this->periodWeekStart();

        return $start ? $start->addDays(6)->endOfDay() : null;
    }

    /**
     * Monday 00:00:00 of the ISO week encoded in `period` (app timezone).
     */
    private function periodWeekStart(): ?CarbonImmutable
    {
        $raw = $this->attributes['period'] ?? null;
        if ($raw === null) {
            return null;
        }
        $period = (int) $raw;
        if ($period <= 0) {
            return null;
        }
        $isoYear = intdiv($period, 100);
        $week = $period % 100;
        if ($week < 1 || $week > 53 || $isoYear < 1970 || $isoYear > 2100) {
            return null;
        }
        $tz = config('app.timezone', 'UTC');
        try {
            return CarbonImmutable::now($tz)->setISODate($isoYear, $week, 1)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
