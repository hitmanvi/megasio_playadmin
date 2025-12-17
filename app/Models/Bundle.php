<?php

namespace App\Models;

class Bundle extends Model
{
    protected $table = 'megasio_play_api.bundles';

    protected $fillable = [
        'name',
        'description',
        'icon',
        'gold_coin',
        'social_coin',
        'original_price',
        'discount_price',
        'currency',
        'stock',
        'enabled',
        'sort_id',
    ];

    protected $casts = [
        'gold_coin' => 'decimal:8',
        'social_coin' => 'decimal:8',
        'original_price' => 'decimal:8',
        'discount_price' => 'decimal:8',
        'stock' => 'integer',
        'enabled' => 'boolean',
        'sort_id' => 'integer',
    ];

    /**
     * Scope: 按启用状态筛选
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope: 按名称筛选
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', '%' . $name . '%');
    }
}
