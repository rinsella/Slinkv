<?php

namespace App\Services;

use App\Models\BlockedDomain;
use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Support\Str;

class ShortLinkService
{
    public const RESERVED_SLUGS = [
        'admin','dashboard','login','register','logout','api','install.php','install',
        'assets','storage','public','pricing','paket','artikel','faq','terms','privacy',
        'contact','kontak','about','tentang','sitemap.xml','robots.txt','solusi',
        'cara-kerja','refund-policy','acceptable-use-policy','forgot-password',
        'reset-password','verify-email','email','ref','up','i','redirect',
        'favicon.ico','favicon.svg','apple-touch-icon.png','site.webmanifest',
        'abuse','unlock','r','invoice','invoices','qr','q','export','export.csv',
        'audit-report','checkout','billing','health-check','manifest.json',
    ];

    public function generateUniqueSlug(int $length = 6): string
    {
        do {
            $slug = Str::lower(Str::random($length));
        } while (in_array($slug, self::RESERVED_SLUGS, true) || ShortLink::where('slug', $slug)->exists());

        return $slug;
    }

    public function isReserved(string $slug): bool
    {
        return in_array(strtolower($slug), self::RESERVED_SLUGS, true);
    }

    /**
     * Validate destination URL: only http/https, no internal/private hosts.
     * Returns null if OK, error string if invalid.
     */
    public function validateDestination(string $url): ?string
    {
        $url = trim($url);
        if (preg_match('/^(javascript|data|file|ftp|vbscript):/i', $url)) {
            return 'Skema URL tidak diperbolehkan.';
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return 'URL tidak valid.';
        }
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true)) {
            return 'URL harus menggunakan http atau https.';
        }
        $host = strtolower($parts['host'] ?? '');
        if ($host === '' || $host === 'localhost' || str_starts_with($host, '127.') || $host === '0.0.0.0') {
            return 'Host tidak diperbolehkan.';
        }
        if (filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return 'IP privat/internal tidak diperbolehkan.';
        }
        // Block our own short domain to avoid loops
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        if ($appHost && $host === strtolower($appHost)) {
            return 'Tujuan tidak boleh ke domain SlinkV sendiri.';
        }
        // Blocked domain list
        $blocked = BlockedDomain::where('is_active', true)
            ->where(function ($q) use ($host) {
                $q->where('domain', $host)->orWhere('domain', preg_replace('/^www\./', '', $host));
            })->exists();
        if ($blocked) {
            return 'Domain tujuan diblokir oleh administrator.';
        }
        return null;
    }

    public function userOwns(ShortLink $link, User $user): bool
    {
        return $link->user_id === $user->id || $user->isAdmin();
    }
}
