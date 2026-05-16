<?php

namespace App\Services;

class SourceDetectionService
{
    public function detect(?string $referer, array $query = []): string
    {
        $r = strtolower($referer ?? '');

        if (!empty($query['fbclid']) || str_contains($r, 'facebook.com') || str_contains($r, 'fb.com')) return 'Facebook';
        if (str_contains($r, 'instagram.com')) return 'Instagram';
        if (!empty($query['ttclid']) || str_contains($r, 'tiktok.com')) return 'TikTok';
        if (!empty($query['gclid']) || (str_contains($r, 'google.') && !str_contains($r, 'youtube'))) return 'Google';
        if (str_contains($r, 'youtube.com') || str_contains($r, 'youtu.be')) return 'YouTube';
        if (str_contains($r, 'whatsapp') || str_contains($r, 'wa.me')) return 'WhatsApp';
        if (str_contains($r, 't.me') || str_contains($r, 'telegram')) return 'Telegram';
        if (str_contains($r, 'twitter.com') || str_contains($r, 'x.com')) return 'Twitter/X';
        if (str_contains($r, 'shopee')) return 'Shopee';
        if (str_contains($r, 'tokopedia')) return 'Tokopedia';
        if (str_contains($r, 'lazada')) return 'Lazada';
        if (($query['utm_medium'] ?? '') === 'email') return 'Email';
        if ($r === '') return 'Direct';

        return 'Unknown';
    }
}
