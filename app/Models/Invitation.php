<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    protected $table = 'megasio_play_api.invitations';

    protected $fillable = [
        'inviter_id',
        'invitee_id',
        'total_reward',
        'status',
    ];

    protected $casts = [
        'total_reward' => 'decimal:2',
    ];

    /**
     * Invitation status constants.
     */
    const STATUS_INACTIVE = 'inactive';
    const STATUS_ACTIVE = 'active';

    /**
     * Get the inviter (the user who sent the invitation).
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    /**
     * Get the invitee (the user who was invited).
     */
    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }

    /**
     * Scope to filter by inviter.
     */
    public function scopeByInviter($query, $inviterId)
    {
        return $query->where('inviter_id', $inviterId);
    }

    /**
     * Scope to filter by invitee.
     */
    public function scopeByInvitee($query, $inviteeId)
    {
        return $query->where('invitee_id', $inviteeId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter inactive invitations.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }

    /**
     * Scope to filter active invitations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Activate the invitation.
     */
    public function activate(): bool
    {
        if ($this->status === self::STATUS_INACTIVE) {
            $this->status = self::STATUS_ACTIVE;
            return $this->save();
        }
        return false;
    }
}
