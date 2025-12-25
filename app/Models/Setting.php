<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use App\Services\CacheInvalidator;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'is_encrypted'];

    /**
     * Cache TTL in seconds (24 hours)
     */
    const CACHE_TTL = 86400;

    /**
     * Get a setting value by key with caching
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", self::CACHE_TTL, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return $setting->is_encrypted
                ? Crypt::decryptString($setting->value)
                : $setting->value;
        });
    }

    /**
     * Set a setting value and clear cache
     */
    public static function set(string $key, $value, string $group = 'general', bool $encrypt = false): void
    {
        $encryptedValue = $encrypt ? Crypt::encryptString($value) : $value;

        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $encryptedValue,
                'group' => $group,
                'is_encrypted' => $encrypt
            ]
        );

        // Clear cache for this setting and its group
        CacheInvalidator::clearSettings($key, $group);
    }

    /**
     * Get all settings for a group with caching
     */
    public static function getGroup(string $group): array
    {
        return Cache::remember("settings.group.{$group}", self::CACHE_TTL, function () use ($group) {
            $settings = static::where('group', $group)->get();

            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = $setting->is_encrypted
                    ? Crypt::decryptString($setting->value)
                    : $setting->value;
            }

            return $result;
        });
    }

    /**
     * Boot method - clear cache when settings change
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            CacheInvalidator::clearSettings($setting->key, $setting->group);
        });

        static::deleted(function ($setting) {
            CacheInvalidator::clearSettings($setting->key, $setting->group);
        });
    }
}
