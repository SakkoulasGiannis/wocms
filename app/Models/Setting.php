<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'is_encrypted'];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return $setting->is_encrypted
            ? Crypt::decryptString($setting->value)
            : $setting->value;
    }

    /**
     * Set a setting value
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
    }

    /**
     * Get all settings for a group
     */
    public static function getGroup(string $group): array
    {
        $settings = static::where('group', $group)->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->is_encrypted
                ? Crypt::decryptString($setting->value)
                : $setting->value;
        }

        return $result;
    }
}
