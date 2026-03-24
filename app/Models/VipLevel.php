<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VipLevel extends Model
{
    protected $table = 'megasio_play_api.vip_levels';

    protected $fillable = [
        'group_id',
        'level',
        'required_exp',
        'description',
        'benefits',
        'sort_id',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'group_id' => 'integer',
            'required_exp' => 'integer',
            'benefits' => 'array',
            'sort_id' => 'integer',
            'enabled' => 'boolean',
        ];
    }

    /**
     * Get the group this VIP level belongs to.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(VipLevelGroup::class, 'group_id');
    }

    /**
     * Scope to filter by enabled status.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to filter by level.
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope to order by sort_id.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('level', 'asc');
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
     * Ordered distinct level numbers (for UserVip rank / progression).
     *
     * @return list<int>
     */
    public static function levelKeys(): array
    {
        return static::query()
            ->orderBy('level')
            ->pluck('level')
            ->unique()
            ->values()
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    public static function requiredExpFor(int $level): float
    {
        $exp = static::query()->where('level', $level)->value('required_exp');

        return $exp !== null ? (float) $exp : 0.0;
    }

    /**
     * Snapshot for a level row (used by UserVip).
     *
     * @return array<string, mixed>|null
     */
    public static function infoForLevel(int $level): ?array
    {
        $row = static::query()->where('level', $level)->with('group')->first();
        if ($row === null) {
            return null;
        }

        return [
            'level' => (int) $row->level,
            'group_id' => $row->group_id,
            'required_exp' => (float) $row->required_exp,
            'description' => $row->description,
            'benefits' => $row->benefits ?? [],
            'sort_id' => $row->sort_id,
            'enabled' => (bool) $row->enabled,
            'group' => $row->group?->toArray(),
        ];
    }
}
