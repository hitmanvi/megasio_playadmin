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
     * Scope to filter by document number.
     */
    public function scopeByDocumentNumber($query, $documentNumber)
    {
        return $query->where('document_number', 'like', "%{$documentNumber}%");
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

