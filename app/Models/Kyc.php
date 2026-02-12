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
     * 
     * 流程：
     * 1. pending -> approved/rejected (初审)
     * 2. approved -> advanced_pending (提交高级认证)
     * 3. advanced_pending -> advanced_approved/advanced_rejected (高级认证审核)
     * 4. advanced_approved -> enhanced_pending (提交增强认证)
     * 5. enhanced_pending -> enhanced_approved/enhanced_rejected (增强认证审核)
     */
    const STATUS_PENDING = 'pending';                     // 初审待审核
    const STATUS_APPROVED = 'approved';                   // 初审通过（可提交高级认证）
    const STATUS_REJECTED = 'rejected';                   // 初审拒绝
    const STATUS_ADVANCED_PENDING = 'advanced_pending';   // 高级认证待审核
    const STATUS_ADVANCED_APPROVED = 'advanced_approved'; // 高级认证通过（可提交增强认证）
    const STATUS_ADVANCED_REJECTED = 'advanced_rejected'; // 高级认证拒绝
    const STATUS_ENHANCED_PENDING = 'enhanced_pending';   // 增强认证待审核
    const STATUS_ENHANCED_APPROVED = 'enhanced_approved'; // 增强认证通过（完成）
    const STATUS_ENHANCED_REJECTED = 'enhanced_rejected'; // 增强认证拒绝

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
     * Scope: KYC 已激活（已通过初审或进入更高级别，排除 pending/rejected）
     */
    public function scopeActivated($query)
    {
        return $query->whereIn('status', [
            self::STATUS_APPROVED,
            self::STATUS_ADVANCED_PENDING,
            self::STATUS_ADVANCED_APPROVED,
            self::STATUS_ADVANCED_REJECTED,
            self::STATUS_ENHANCED_PENDING,
            self::STATUS_ENHANCED_APPROVED,
            self::STATUS_ENHANCED_REJECTED,
        ]);
    }

    /**
     * 判断用户是否已 KYC 激活
     */
    public static function isUserActivated(int $userId): bool
    {
        return static::where('user_id', $userId)->activated()->exists();
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

