<?php

namespace Codexalta\Vaultix\Models;

use Illuminate\Database\Eloquent\Model;

class VaultixSetting extends Model
{
    protected $table = 'vaultix_settings';
    protected $fillable = ['key', 'value'];

    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if (!$setting) return $default;
        
        // Auto-decode JSON if it looks like one
        $value = $setting->value;
        $decoded = json_decode($value, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
    }

    public static function set($key, $value)
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }
        
        return self::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
