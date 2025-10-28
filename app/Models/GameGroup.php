<?php

namespace App\Models;

use App\Traits\Translatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GameGroup extends Model
{
    use Translatable;

    protected $table = 'megasio_play_api.game_groups';

    protected $fillable = [
        'name',
        'category',
        'sort_id',
        'app_limit',
        'web_limit',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'sort_id' => 'integer',
            'app_limit' => 'integer',
            'web_limit' => 'integer',
            'enabled' => 'boolean',
        ];
    }

    /**
     * Get the games for the game group.
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'megasio_play_api.game_group_game')
            ->withPivot('sort_id')
            ->withTimestamps()
            ->orderBy('game_group_game.sort_id');
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
     * @param array $names ['en' => 'New Games', 'zh-CN' => '新游戏']
     * @return void
     */
    public function setNames(array $names): void
    {
        $this->setTranslations('name', $names);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by enabled status.
     */
    public function scopeByEnabled($query, $enabled)
    {
        return $query->where('enabled', $enabled);
    }

    /**
     * Scope to filter by enabled game groups.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to filter by disabled game groups.
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
        return $query->orderBy('sort_id');
    }
}

