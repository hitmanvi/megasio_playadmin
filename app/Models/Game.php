<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $table = 'megasio_play_api.games';

    protected $fillable = [
        'brand_id',
        'category_id',
        'theme_id',
        'out_id',
        'name',
        'thumbnail',
        'sort_id',
        'enabled',
        'memo',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'sort_id' => 'integer',
    ];

    /**
     * Get the brand that owns the game.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the category that owns the game.
     */
    public function category()
    {
        return $this->belongsTo(Tag::class, 'category_id');
    }

    /**
     * Get the theme that owns the game.
     */
    public function theme()
    {
        return $this->belongsTo(Tag::class, 'theme_id');
    }

    /**
     * Scope to filter by name.
     */
    public function scopeByName($query, $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /**
     * Scope to filter by out_id.
     */
    public function scopeByOutId($query, $outId)
    {
        return $query->where('out_id', 'like', "%{$outId}%");
    }

    /**
     * Scope to filter by enabled status.
     */
    public function scopeByEnabled($query, $enabled)
    {
        return $query->where('enabled', $enabled);
    }

    /**
     * Scope to filter by enabled games.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to filter by disabled games.
     */
    public function scopeDisabled($query)
    {
        return $query->where('enabled', false);
    }

    /**
     * Scope to filter by brand name.
     */
    public function scopeByBrandName($query, $brandName)
    {
        return $query->whereHas('brand', function ($q) use ($brandName) {
            $q->where('name', 'like', "%{$brandName}%");
        });
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to filter by theme.
     */
    public function scopeByTheme($query, $themeId)
    {
        return $query->where('theme_id', $themeId);
    }

    /**
     * Scope to filter by theme readiness.
     */
    public function scopeByThemeReady($query, $ready)
    {
        return $query->whereHas('theme', function ($q) use ($ready) {
            $q->where('enabled', $ready);
        });
    }

    /**
     * Scope to filter by localization setup.
     */
    public function scopeByLocalizationSet($query, $set)
    {
        if ($set) {
            return $query->whereNotNull('name');
        } else {
            return $query->whereNull('name');
        }
    }

    /**
     * Scope to filter by thumbnail uploaded.
     */
    public function scopeByThumbnailUploaded($query, $uploaded)
    {
        if ($uploaded) {
            return $query->whereNotNull('thumbnail')->where('thumbnail', '!=', '');
        } else {
            return $query->where(function ($q) {
                $q->whereNull('thumbnail')->orWhere('thumbnail', '');
            });
        }
    }

    /**
     * Scope to filter by memo presence.
     */
    public function scopeByMemoPresent($query, $present)
    {
        if ($present) {
            return $query->whereNotNull('memo')->where('memo', '!=', '');
        } else {
            return $query->where(function ($q) {
                $q->whereNull('memo')->orWhere('memo', '');
            });
        }
    }

    /**
     * Scope to order by sort_id.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_id');
    }
}
