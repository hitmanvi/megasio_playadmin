<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Redeem extends Model
{
    protected $table = 'megasio_play_api.redeems';

    protected $fillable = [
        'user_id',
        'order_no',
        'out_trade_no',
        'sc_amount',
        'exchange_rate',
        'usd_amount',
        'actual_amount',
        'fee',
        'payment_method_id',
        'withdraw_info',
        'extra_info',
        'status',
        'pay_status',
        'approved',
        'user_ip',
        'completed_at',
        'note',
    ];

    /**
     * Redeem status constants.
     */
    const STATUS_PENDING = 'PENDING';
    const STATUS_PROCESSING = 'PROCESSING';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_FAILED = 'FAILED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_REJECTED = 'REJECTED';

    /**
     * Pay status constants.
     */
    const PAY_STATUS_PENDING = 'PENDING';
    const PAY_STATUS_PAID = 'PAID';
    const PAY_STATUS_FAILED = 'FAILED';
    const PAY_STATUS_CANCELLED = 'CANCELLED';

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'payment_method_id' => 'integer',
            'sc_amount' => 'decimal:8',
            'exchange_rate' => 'decimal:8',
            'usd_amount' => 'decimal:8',
            'actual_amount' => 'decimal:8',
            'fee' => 'decimal:8',
            'approved' => 'boolean',
            'withdraw_info' => 'array',
            'extra_info' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the payment method that owns the redeem.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the user that owns the redeem.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
     * Scope to filter by approved status.
     */
    public function scopeByApproved($query, $approved)
    {
        return $query->where('approved', $approved);
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
