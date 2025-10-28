<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $table = 'megasio_play_api.orders';

    protected $fillable = [
        'user_id',
        'game_id',
        'brand_id',
        'amount',
        'payout',
        'status',
        'currency',
        'payment_currency',
        'payment_amount',
        'payment_payout',
        'notes',
        'finished_at',
        'order_id',
        'out_id',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'game_id' => 'integer',
            'brand_id' => 'integer',
            'amount' => 'decimal:8',
            'payout' => 'decimal:8',
            'payment_amount' => 'decimal:8',
            'payment_payout' => 'decimal:8',
            'version' => 'integer',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * Get the game that owns the order.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the brand that owns the order.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Scope to filter by order_id.
     */
    public function scopeByOrderId($query, $orderId)
    {
        return $query->where('order_id', 'like', "%{$orderId}%");
    }

    /**
     * Scope to filter by out_id.
     */
    public function scopeByOutId($query, $outId)
    {
        return $query->where('out_id', 'like', "%{$outId}%");
    }

    /**
     * Scope to filter by currency.
     */
    public function scopeByCurrency($query, $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Scope to filter by amount range.
     */
    public function scopeByAmountRange($query, $min, $max)
    {
        if ($min !== null) {
            $query->where('amount', '>=', $min);
        }
        if ($max !== null) {
            $query->where('amount', '<=', $max);
        }
        return $query;
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        return $query;
    }

    /**
     * Scope to filter by game name.
     */
    public function scopeByGameName($query, $gameName)
    {
        return $query->whereHas('game', function ($q) use ($gameName) {
            $q->where('name', 'like', "%{$gameName}%");
        });
    }
}

