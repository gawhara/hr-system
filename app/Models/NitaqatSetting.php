<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class NitaqatSetting extends Model
{
    protected $fillable = ['key', 'value', 'label_ar', 'description_ar', 'verified_at'];

    protected $casts = [
        'verified_at' => 'date',
    ];

    public static function getFloat(string $key, float $default = 0.0): float
    {
        $value = Cache::remember("nitaqat_setting_{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->value('value');
        });

        return $value !== null ? (float) $value : $default;
    }

    protected static function booted(): void
    {
        static::saved(fn (NitaqatSetting $setting) => Cache::forget("nitaqat_setting_{$setting->key}"));
        static::deleted(fn (NitaqatSetting $setting) => Cache::forget("nitaqat_setting_{$setting->key}"));
    }
}
