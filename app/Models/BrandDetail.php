<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandDetail extends Model
{
    protected $table = 'brand_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'brand_id',
        'coin',
        'support',
        'configured',
        'game_count',
        'enabled',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'support' => 'boolean',
            'configured' => 'boolean',
            'enabled' => 'boolean',
        ];
    }

    /**
     * Get the brand that owns the detail.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Scope to filter by enabled status.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to filter by support status.
     */
    public function scopeSupported($query)
    {
        return $query->where('support', true);
    }

    /**
     * Scope to filter by configured status.
     */
    public function scopeConfigured($query)
    {
        return $query->where('configured', true);
    }

    /**
     * Scope to filter by coin type.
     */
    public function scopeByCoin($query, $coin)
    {
        return $query->where('coin', $coin);
    }
}
