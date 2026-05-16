<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class GeoIpService
{
    /**
     * Returns ['country_code' => 'XX', 'country_name' => '...', 'city' => null]
     * Always returns something - never throws.
     */
    public function lookup(?string $ip): array
    {
        $default = ['country_code' => null, 'country_name' => 'Unknown', 'city' => null];

        if (!$ip || $this->isPrivate($ip)) {
            return ['country_code' => 'LO', 'country_name' => 'Local', 'city' => null];
        }

        return Cache::remember("geoip:{$ip}", 86400, function () use ($ip, $default) {
            try {
                // Try CF header style header-only (skipped here). Try free ip-api as last resort only if outbound allowed.
                // For safety we DO NOT make outbound HTTP from the redirect path by default.
                return $default;
            } catch (\Throwable $e) {
                return $default;
            }
        });
    }

    private function isPrivate(string $ip): bool
    {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
