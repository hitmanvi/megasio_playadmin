<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BundlePurchase extends Model
{
    protected $table = 'megasio_play_api.bundle_purchases';

    protected $fillable = [
        'order_no',
        'user_id',
        'bundle_id',
        'payment_method_id',
        'gold_coin',
        'social_coin',
        'amount',
        'currency',
        'out_trade_no',
        'status',
        'pay_status',
        'user_ip',
        'payment_info',
        'notes',
        'paid_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'bundle_id' => 'integer',
            'payment_method_id' => 'integer',
            'gold_coin' => 'decimal:8',
            'social_coin' => 'decimal:8',
            'amount' => 'decimal:8',
            'payment_info' => 'array',
            'paid_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the purchase.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the bundle that was purchased.
     */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class, 'bundle_id');
    }

    /**
     * Get the payment method used.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Scope to filter by order_no.
     */
    public function scopeByOrderNo($query, $orderNo)
    {
        return $query->where('order_no', 'like', "%{$orderNo}%");
    }

    /**
     * Scope to filter by out_trade_no.
     */
    public function scopeByOutTradeNo($query, $outTradeNo)
    {
        return $query->where('out_trade_no', 'like', "%{$outTradeNo}%");
    }

    /**
     * Scope to filter by bundle_id.
     */
    public function scopeByBundle($query, $bundleId)
    {
        return $query->where('bundle_id', $bundleId);
    }

    /**
     * Scope to filter by payment method.
     */
    public function scopeByPaymentMethod($query, $paymentMethodId)
    {
        return $query->where('payment_method_id', $paymentMethodId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by pay_status.
     */
    public function scopeByPayStatus($query, $payStatus)
    {
        return $query->where('pay_status', $payStatus);
    }

    /**
     * Scope to filter by user email or phone.
     */
    public function scopeByUserEmailOrPhone($query, $emailOrPhone)
    {
        return $query->whereHas('user', function ($q) use ($emailOrPhone) {
            $q->where('email', 'like', "%{$emailOrPhone}%")
              ->orWhere('phone', 'like', "%{$emailOrPhone}%");
        });
    }
}
