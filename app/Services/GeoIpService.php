<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoIpService
{
    /**
     * Returns ['country_code' => 'XX', 'country_name' => '...', 'city' => null]
     * Always returns something - never throws.
     */
    public function lookup(?string $ip): array
    {
        $unknown = ['country_code' => null, 'country_name' => 'Unknown', 'city' => null];

        if (!$ip) {
            return $unknown;
        }

        if ($this->isPrivate($ip)) {
            return ['country_code' => 'LO', 'country_name' => 'Local', 'city' => null];
        }

        return Cache::remember("geoip:{$ip}", 86400, function () use ($ip, $unknown) {
            try {
                // ip-api.com free endpoint: 45 req/min, no API key.
                // fields bitmask: status(16384)+country(1)+countryCode(2)+city(256) = 16643
                $res = Http::timeout(2)
                    ->connectTimeout(1)
                    ->acceptJson()
                    ->get("http://ip-api.com/json/{$ip}", ['fields' => 16643]);

                if (!$res->ok()) {
                    return $unknown;
                }

                $data = $res->json();
                if (!is_array($data) || ($data['status'] ?? null) !== 'success') {
                    return $unknown;
                }

                return [
                    'country_code' => $data['countryCode'] ?? null,
                    'country_name' => $data['country'] ?? 'Unknown',
                    'city' => $data['city'] ?? null,
                ];
            } catch (\Throwable $e) {
                Log::debug('GeoIP lookup failed', ['ip' => $ip, 'err' => $e->getMessage()]);
                return $unknown;
            }
        });
    }

    private function isPrivate(string $ip): bool
    {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
