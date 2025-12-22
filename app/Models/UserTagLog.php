<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTagLog extends Model
{
    protected $table = 'megasio_play_api.user_tag_logs';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'tag_id',
        'value',
        'reason',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'tag_id' => 'integer',
    ];

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tag.
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    /**
     * Scope: 按 value 筛选
     */
    public function scopeByValue($query, string $value)
    {
        return $query->where('value', 'like', '%' . $value . '%');
    }

    /**
     * Scope: 按 tag_id 筛选
     */
    public function scopeByTag($query, int $tagId)
    {
        return $query->where('tag_id', $tagId);
    }

    /**
     * Scope: 按用户状态筛选
     */
    public function scopeByUserStatus($query, string $status)
    {
        return $query->whereHas('user', function ($q) use ($status) {
            $q->where('status', $status);
        });
    }
}
