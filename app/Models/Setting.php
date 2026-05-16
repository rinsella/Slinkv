<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::rememberForever("setting.{$key}", function () use ($key) {
            return static::where('key', $key)->value('value');
        });

        return $value ?? $default;
    }

    public static function set(string $key, mixed $value, string $type = 'text', ?string $group = null): void
    {
        static::updateOrCreate(['key' => $key], [
            'value' => is_scalar($value) || $value === null ? $value : json_encode($value),
            'type' => $type,
            'group' => $group,
        ]);
        Cache::forget("setting.{$key}");
    }
}
