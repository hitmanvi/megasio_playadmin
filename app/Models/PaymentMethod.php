<?php

namespace App\Models;

class PaymentMethod extends Model
{
    protected $table = 'megasio_play_api.payment_methods';
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'icon',
        'name',
        'display_name',
        'currency',
        'currency_type',
        'type',
        'is_fiat',
        'amounts',
        'max_amount',
        'min_amount',
        'enabled',
        'synced_at',
        'notes',
        'sort_id',
        'crypto_info',
        'fields',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amounts' => 'array',
            'max_amount' => 'decimal:8',
            'min_amount' => 'decimal:8',
            'enabled' => 'boolean',
            'is_fiat' => 'boolean',
            'synced_at' => 'datetime',
            'crypto_info' => 'array',
            'fields' => 'array',
        ];
    }

    /**
     * Scope to filter by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by currency.
     */
    public function scopeByCurrency($query, $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Scope to filter by enabled status.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to order by sort_id.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_id', 'asc');
    }
}

