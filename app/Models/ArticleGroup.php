<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleGroup extends Model
{
    protected $table = 'megasio_play_api.article_groups';

    protected $fillable = [
        'name',
        'icon',
        'parent_id',
        'enabled',
        'sort_id',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'sort_id' => 'integer',
        'parent_id' => 'integer',
    ];

    /**
     * Get the parent group.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ArticleGroup::class, 'parent_id');
    }

    /**
     * Get the child groups.
     */
    public function children(): HasMany
    {
        return $this->hasMany(ArticleGroup::class, 'parent_id');
    }

    /**
     * Get the articles in this group.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'group_id');
    }

    /**
     * Scope to filter by enabled status.
     */
    public function scopeByEnabled($query, $enabled)
    {
        return $query->where('enabled', $enabled);
    }

    /**
     * Scope to filter enabled groups.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to filter disabled groups.
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
     * Scope to filter root groups (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to filter by parent_id.
     */
    public function scopeByParent($query, $parentId)
    {
        if ($parentId === null) {
            return $query->whereNull('parent_id');
        }
        return $query->where('parent_id', $parentId);
    }

    /**
     * Scope to filter by name.
     */
    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }
}
