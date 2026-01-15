<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    protected $table = 'megasio_play_api.articles';

    protected $fillable = [
        'title',
        'content',
        'group_id',
        'enabled',
        'sort_id',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'sort_id' => 'integer',
        'group_id' => 'integer',
    ];

    /**
     * Get the group this article belongs to.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ArticleGroup::class, 'group_id');
    }

    /**
     * Scope to filter by enabled status.
     */
    public function scopeByEnabled($query, $enabled)
    {
        return $query->where('enabled', $enabled);
    }

    /**
     * Scope to filter enabled articles.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to filter disabled articles.
     */
    public function scopeDisabled($query)
    {
        return $query->where('enabled', false);
    }

    /**
     * Scope to order by sort_id.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_id', 'asc')->orderBy('id', 'asc');
    }

    /**
     * Scope to filter by group_id.
     */
    public function scopeByGroup($query, $groupId)
    {
        if ($groupId === null) {
            return $query->whereNull('group_id');
        }
        return $query->where('group_id', $groupId);
    }

    /**
     * Scope to filter by title.
     */
    public function scopeByTitle($query, $title)
    {
        return $query->where('title', 'like', "%{$title}%");
    }
}
