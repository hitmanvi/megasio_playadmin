<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Translatable;

class Game extends Model
{
    use HasFactory, Translatable;

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
        'languages',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'sort_id' => 'integer',
        'languages' => 'array',
    ];

    /**
     * Get the brand that owns the game.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the category for the game.
     */
    public function category()
    {
        return $this->belongsTo(GameCategory::class);
    }

    /**
     * Get the themes for the game.
     */
    public function themes()
    {
        return $this->belongsToMany(Theme::class, 'megasio_play_api.game_theme')
                    ->withTimestamps();
    }

    /**
     * Get the game groups for the game.
     */
    public function gameGroups()
    {
        return $this->belongsToMany(GameGroup::class, 'megasio_play_api.game_group_game', 'game_id', 'game_group_id')
            ->withPivot('sort_id')
            ->withTimestamps()
            ->orderBy('game_group_game.sort_id');
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
     * Scope to filter by multiple brand names.
     */
    public function scopeByBrandNames($query, $brandNames)
    {
        return $query->whereHas('brand', function ($q) use ($brandNames) {
            $q->whereIn('name', $brandNames);
        });
    }

    /**
     * Scope to filter by multiple brand IDs.
     */
    public function scopeByBrandIds($query, $brandIds)
    {
        return $query->whereIn('brand_id', $brandIds);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to filter by multiple categories.
     */
    public function scopeByCategories($query, $categoryIds)
    {
        return $query->whereIn('category_id', $categoryIds);
    }

    /**
     * Scope to filter by multiple themes.
     */
    public function scopeByThemes($query, $themeIds)
    {
        return $query->whereHas('themes', function ($q) use ($themeIds) {
            $q->whereIn('themes.id', $themeIds);
        });
    }

    /**
     * Scope to filter by theme readiness.
     */
    public function scopeByThemeReady($query, $ready)
    {
        if ($ready) {
            return $query->whereHas('themes', function ($q) {
                $q->where('enabled', true);
            });
        } else {
            return $query->whereDoesntHave('themes')->orWhereHas('themes', function ($q) {
                $q->where('enabled', false);
            });
        }
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

    /**
     * Get the translated name for the current locale.
     *
     * @param string|null $locale
     * @return string|null
     */
    public function getName(?string $locale = null): ?string
    {
        return $this->getTranslatedAttribute('name', $locale);
    }

    /**
     * Set the translated name for a specific locale.
     *
     * @param string $name
     * @param string|null $locale
     * @return void
     */
    public function setName(string $name, ?string $locale = null): void
    {
        $this->setTranslation('name', $name, $locale);
    }

    /**
     * Get all translated names.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllNames()
    {
        return $this->getTranslations('name');
    }

    /**
     * Set multiple translated names.
     *
     * @param array $names ['en' => 'Game Name', 'zh-CN' => '游戏名称']
     * @return void
     */
    public function setNames(array $names): void
    {
        $this->setTranslations('name', $names);
    }
}
