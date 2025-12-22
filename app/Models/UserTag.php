<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTag extends Model
{
    protected $table = 'megasio_play_api.user_tags';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'tag_id',
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
}
