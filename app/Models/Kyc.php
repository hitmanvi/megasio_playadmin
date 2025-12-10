<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kyc extends Model
{
    protected $table = 'megasio_play_api.kycs';

    protected $fillable = [
        'user_id',
        'name',
        'birthdate',
        'document_front',
        'document_back',
        'document_number',
        'selfie',
        'status',
        'reject_reason',
    ];

    /**
     * KYC status constants.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * Get the user that owns the KYC.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by user ID.
     */
    public function scopeByUserId($query, $userId)
    {
        return $query->where('user_id', $userId);
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

    /**
     * Scope to filter pending KYCs.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to filter approved KYCs.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to filter rejected KYCs.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Check if KYC is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if KYC is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if KYC is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}

