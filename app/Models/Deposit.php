<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposit extends Model
{
    protected $table = 'megasio_play_api.deposits';

    protected $fillable = [
        'user_id',
        'order_no',
        'out_trade_no',
        'currency',
        'amount',
        'actual_amount',
        'payment_method_id',
        'deposit_info',
        'extra_info',
        'status',
        'pay_status',
        'pay_fee',
        'user_ip',
        'expired_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'payment_method_id' => 'integer',
            'amount' => 'decimal:8',
            'actual_amount' => 'decimal:8',
            'pay_fee' => 'decimal:8',
            'deposit_info' => 'array',
            'extra_info' => 'array',
            'expired_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * Get the payment method that owns the deposit.
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
     * Note: This assumes users table exists in megasio_play_api database
     */
    public function scopeByUserEmailOrPhone($query, $emailOrPhone)
    {
        return $query->whereHas('user', function ($q) use ($emailOrPhone) {
            $q->where('email', 'like', "%{$emailOrPhone}%")
              ->orWhere('phone', 'like', "%{$emailOrPhone}%");
        });
    }

    /**
     * Get the user that owns the deposit.
     * Note: This assumes users table exists in megasio_play_api database
     */
    public function user(): BelongsTo
    {
        // Assuming users table exists in megasio_play_api database
        return $this->belongsTo(User::class, 'user_id');
    }
}

