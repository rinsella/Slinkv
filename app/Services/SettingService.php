<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const CACHE_KEY = 'settings.all';

    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            return Setting::all()->mapWithKeys(fn ($s) => [$s->key => $s->value])->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $v = $this->get($key);
        if ($v === null) return $default;
        return in_array(strtolower((string) $v), ['1', 'true', 'yes', 'on'], true);
    }

    public function int(string $key, int $default = 0): int
    {
        $v = $this->get($key);
        return $v === null || $v === '' ? $default : (int) $v;
    }

    public function set(string $key, mixed $value, string $type = 'text', ?string $group = null): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            array_filter([
                'value' => is_bool($value) ? ($value ? '1' : '0') : (is_array($value) ? json_encode($value) : (string) $value),
                'type' => $type,
                'group' => $group,
            ], fn ($v) => $v !== null)
        );
        $this->flush();
    }

    public function setMany(array $values, ?string $group = null): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, 'text', $group);
        }
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
