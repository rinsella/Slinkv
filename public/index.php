<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// ─────────────────────────────────────────────────────────────────────────────
// Slinkv pre-boot edge guard (v0.4.12)
// Block bot floods BEFORE composer autoload — avoids ~100ms Laravel boot
// per malicious request. Uses APCu if available (in-memory, ~0.05ms),
// otherwise small flat files in storage/framework/cache/edge (~0.5ms).
// Limits: 20 hits / 10s per IP -> 5 min block. Only guards /{slug} paths.
// Excludes known app routes via denylist (dashboard, login, admin, etc).
// ─────────────────────────────────────────────────────────────────────────────
(static function (): void {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    // Quick reject for non-redirect paths.
    // Strategy: only guard single-segment paths that look like short slugs
    // AND aren't known app routes.
    $segments = explode('/', trim($path, '/'));
    if (count($segments) !== 1) {
        return; // multi-segment -> never a /{slug} redirect
    }
    $first = $segments[0];
    if ($first === '') {
        return; // home page
    }

    // App routes / static files that share the slug character set —
    // these must NEVER be throttled.
    static $appPaths = [
        // auth
        'login', 'register', 'logout',
        'forgot-password', 'reset-password', 'password',
        'email', 'verify',
        // areas
        'dashboard', 'admin', 'api',
        // public pages
        'pricing', 'paket', 'solusi', 'cara-kerja', 'artikel',
        'faq', 'tentang', 'kontak', 'terms', 'privacy',
        'refund-policy', 'acceptable-use-policy', 'abuse',
        'quick-shorten',
        // assets / build
        'build', 'storage', 'vendor', 'css', 'js', 'img', 'images', 'assets',
        // reserved files
        'index.php', 'robots.txt', 'sitemap.xml', 'favicon.ico',
        'site.webmanifest', 'apple-touch-icon.png',
        // hotfix / install / debug helpers
        'install.php', 'debug.php', 'fix-bot-ddos.php', 'fix-clear-logs.php',
        'fix-edge-guard.php', 'fix-edge-guard-v2.php', 'fix-activate-user.php',
        'fix-mobile-sidebar.php', 'fix-runtime-dirs.php',
    ];
    if (in_array($first, $appPaths, true)) {
        return;
    }

    // Must look like a short slug: 1-32 chars of [A-Za-z0-9_-]
    if (!preg_match('#^[A-Za-z0-9_\-]{1,32}$#', $first)) {
        return;
    }

    // Best-effort client IP (Cloudflare > XFF > REMOTE_ADDR)
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
    if ($ip === '' && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    if ($ip === '') {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }
    if ($ip === '') {
        return;
    }

    $key   = substr(sha1($ip), 0, 24);
    $limit = 20;   // hits per window
    $win   = 10;   // window seconds
    $ban   = 300;  // block TTL seconds

    $sendBlock = static function (): void {
        http_response_code(429);
        header('Retry-After: 300');
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');
        echo "Too Many Requests\n";
        exit;
    };

    // Path A — APCu (fastest)
    if (function_exists('apcu_enabled') && apcu_enabled()) {
        if (apcu_fetch('eb_' . $key)) {
            $sendBlock();
        }
        $cnt = apcu_inc('ec_' . $key, 1, $ok, $win);
        if ($cnt !== false && $cnt > $limit) {
            apcu_store('eb_' . $key, 1, $ban);
            $sendBlock();
        }
        return;
    }

    // Path B — flat-file fallback
    $dir = __DIR__ . '/../storage/framework/cache/edge';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $blockFile = $dir . '/b_' . $key;
    if (is_file($blockFile) && (filemtime($blockFile) ?: 0) > time() - $ban) {
        $sendBlock();
    }
    $burstFile = $dir . '/c_' . $key;
    $now = time();
    $data = null;
    if (is_file($burstFile)) {
        $raw = @file_get_contents($burstFile);
        if ($raw !== false) {
            $data = @unserialize($raw, ['allowed_classes' => false]);
        }
    }
    if (!is_array($data) || !isset($data['t'], $data['n']) || $data['t'] < $now - $win) {
        $data = ['t' => $now, 'n' => 0];
    }
    $data['n']++;
    if ($data['n'] > $limit) {
        @touch($blockFile);
        $sendBlock();
    }
    @file_put_contents($burstFile, serialize($data), LOCK_EX);
})();
// ─────────────────────────────────────────────────────────────────────────────

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
