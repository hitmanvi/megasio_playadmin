<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $table = 'megasio_play_api.brands';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'provider',
        'restricted_region',
        'sort_id',
        'enabled',
        'maintain_start',
        'maintain_end',
        'maintain_auto',
        'maintain_week_day',
        'icon',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'restricted_region' => 'array',
            'enabled' => 'boolean',
            'maintain_auto' => 'boolean',
        ];
    }

    /**
     * Get the brand details for the brand.
     */
    public function details(): HasMany
    {
        return $this->hasMany(BrandDetail::class);
    }

    /**
     * Scope to filter by enabled status.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to filter by provider.
     */
    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope to order by sort_id.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_id', 'asc');
    }

    /**
     * Check if brand is in maintenance mode.
     */
    public function isInMaintenance(): bool
    {
        if (!$this->maintain_start || !$this->maintain_end) {
            return false;
        }

        $now = now()->format('H:i');
        return $now >= $this->maintain_start && $now <= $this->maintain_end;
    }

    /**
     * Check if region is restricted.
     */
    public function isRegionRestricted(string $region): bool
    {
        if (!$this->restricted_region) {
            return false;
        }

        return in_array($region, $this->restricted_region);
    }
}
