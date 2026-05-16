<?php

namespace App\Services;

class DeviceDetectionService
{
    public function detect(?string $userAgent): array
    {
        $ua = strtolower($userAgent ?? '');

        $deviceType = 'Desktop';
        if ($ua === '') {
            $deviceType = 'Unknown';
        } elseif (str_contains($ua, 'mobile') && !str_contains($ua, 'ipad')) {
            $deviceType = 'Smartphone';
        } elseif (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            $deviceType = 'Tablet';
        } elseif (str_contains($ua, 'bot') || str_contains($ua, 'crawler') || str_contains($ua, 'spider')) {
            $deviceType = 'Bot';
        }

        $browser = match (true) {
            str_contains($ua, 'edg/') || str_contains($ua, 'edge') => 'Edge',
            str_contains($ua, 'samsungbrowser') => 'Samsung Internet',
            str_contains($ua, 'opr/') || str_contains($ua, 'opera') => 'Opera',
            str_contains($ua, 'firefox') => 'Firefox',
            str_contains($ua, 'chrome') && !str_contains($ua, 'edg') => 'Chrome',
            str_contains($ua, 'safari') => 'Safari',
            default => 'Unknown',
        };

        $os = match (true) {
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'iphone') || str_contains($ua, 'ipad') || str_contains($ua, 'ipod') => 'iOS',
            str_contains($ua, 'mac os') || str_contains($ua, 'macintosh') => 'macOS',
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'linux') => 'Linux',
            default => 'Unknown',
        };

        return ['device' => $deviceType, 'browser' => $browser, 'os' => $os];
    }

    public function deviceCategoryForFilter(string $deviceType): string
    {
        return match ($deviceType) {
            'Desktop' => 'desktop',
            'Smartphone' => 'mobile',
            'Tablet' => 'tablet',
            default => 'unknown',
        };
    }
}
