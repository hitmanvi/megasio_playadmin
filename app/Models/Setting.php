<?php

namespace App\Models;

class Setting extends Model
{
    protected $table = 'megasio_play_api.settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'sort_id',
    ];

    protected $casts = [
        'sort_id' => 'integer',
    ];

    /**
     * 获取转换后的值
     */
    public function getCastedValueAttribute()
    {
        return $this->castValue($this->value, $this->type);
    }

    /**
     * 根据类型转换值
     */
    protected function castValue($value, $type)
    {
        if (is_null($value)) {
            return null;
        }

        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * 设置值（自动序列化）
     */
    public function setValueAttribute($value)
    {
        if (is_array($value) || is_object($value)) {
            $this->attributes['value'] = json_encode($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }

    /**
     * Scope: 按分组筛选
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope: 按 key 筛选
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('key', $key);
    }

    /**
     * 静态方法：获取设置值
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->casted_value : $default;
    }

    /**
     * 静态方法：设置值
     */
    public static function setValue(string $key, $value, ?string $type = null)
    {
        $setting = static::where('key', $key)->first();
        
        if ($setting) {
            $setting->value = $value;
            if ($type) {
                $setting->type = $type;
            }
            $setting->save();
            return $setting;
        }

        return null;
    }

    /**
     * 静态方法：获取分组下所有设置
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->mapWithKeys(fn($setting) => [$setting->key => $setting->casted_value])
            ->toArray();
    }
}
