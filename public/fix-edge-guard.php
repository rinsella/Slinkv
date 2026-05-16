<?php
/**
 * Slinkv hotfix — v0.4.11 — Pre-boot edge guard
 *
 * Masalah: walaupun ada RedirectRateLimit middleware (v0.4.7), setiap
 * request bot tetap mem-boot seluruh framework Laravel (composer autoload,
 * service providers, middleware stack) ~80-150ms sebelum di-return 429.
 * Pada serangan 1000 req/s ini langsung membuat CPU 99%.
 *
 * Fix: tambahkan early-exit guard di public/index.php SEBELUM autoload.
 * Bila APCu tersedia, dipakai sebagai in-memory store (~0.05ms/req).
 * Bila tidak, fallback ke file kecil di storage/framework/cache/edge.
 *
 * Default limit: 20 hit / 10 detik per IP -> block 5 menit.
 *
 * Idempotent. Backup ditulis ke <file>.bak-edgeguard.
 *
 * Usage:
 *   1. Upload ke folder public/ di server.
 *   2. Buka https://YOURDOMAIN/fix-edge-guard.php?key=slinkv-fix-2026
 *   3. Hapus file ini setelah laporan [OK].
 */

declare(strict_types=1);

if (function_exists('opcache_invalidate')) {
    @opcache_invalidate(__FILE__, true);
}

const FIX_TOKEN = 'slinkv-fix-2026';

@ini_set('display_errors', '1');
error_reporting(E_ALL);

if (($_GET['key'] ?? '') !== FIX_TOKEN) {
    http_response_code(403);
    exit('Forbidden.');
}

header('Content-Type: text/plain; charset=utf-8');

$BASE = dirname(__DIR__);
$OK   = '[OK]  ';
$SKIP = '[SKIP]';
$FAIL = '[FAIL]';

echo "Slinkv hotfix v0.4.11 — pre-boot edge guard\n";
echo "BASE = $BASE\n\n";

// ---------------------------------------------------------------------------
// STEP 1 — Patch public/index.php
// ---------------------------------------------------------------------------
$indexFile = $BASE . '/public/index.php';
if (!is_file($indexFile)) {
    echo "$FAIL public/index.php missing\n";
    exit;
}

$cur = (string) file_get_contents($indexFile);

if (str_contains($cur, 'Slinkv pre-boot edge guard')) {
    echo "$SKIP edge guard already installed in public/index.php\n";
} else {
    // Locate the maintenance-mode block to insert just before it.
    $anchor = "// Determine if the application is in maintenance mode...";
    if (!str_contains($cur, $anchor)) {
        echo "$FAIL anchor '// Determine if the application is in maintenance mode...' not found.\n";
        echo "       Patch index.php manually using the version on GitHub.\n";
        exit;
    }

    $guard = <<<'PHP'
// ─────────────────────────────────────────────────────────────────────────────
// Slinkv pre-boot edge guard (v0.4.11)
// Block bot floods BEFORE composer autoload — avoids ~100ms Laravel boot
// per malicious request. Uses APCu if available (in-memory, ~0.05ms),
// otherwise small flat files in storage/framework/cache/edge (~0.5ms).
// Limits: 20 hits / 10s per IP -> 5 min block. Only guards /{slug} paths.
// ─────────────────────────────────────────────────────────────────────────────
(static function (): void {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (!preg_match('#^/[A-Za-z0-9_\-]{1,32}/?$#', $path)) {
        return;
    }
    static $reserved = ['/index.php', '/robots.txt', '/favicon.ico', '/sitemap.xml', '/site.webmanifest'];
    if (in_array($path, $reserved, true)) {
        return;
    }

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
    $limit = 20;
    $win   = 10;
    $ban   = 300;

    $sendBlock = static function (): void {
        http_response_code(429);
        header('Retry-After: 300');
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');
        echo "Too Many Requests\n";
        exit;
    };

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

PHP;

    $patched = str_replace($anchor, $guard . "\n" . $anchor, $cur);

    if (!is_file($indexFile . '.bak-edgeguard')) {
        @copy($indexFile, $indexFile . '.bak-edgeguard');
    }
    if (file_put_contents($indexFile, $patched) === false) {
        echo "$FAIL write failed: $indexFile\n";
        exit;
    }
    if (function_exists('opcache_invalidate')) @opcache_invalidate($indexFile, true);
    echo "$OK pre-boot edge guard installed in public/index.php\n";
}

// ---------------------------------------------------------------------------
// STEP 2 — Ensure edge cache dir exists & writable (fallback path)
// ---------------------------------------------------------------------------
$edgeDir = $BASE . '/storage/framework/cache/edge';
if (!is_dir($edgeDir)) {
    if (@mkdir($edgeDir, 0775, true)) {
        echo "$OK created $edgeDir\n";
    } else {
        echo "$FAIL could not create $edgeDir (run: mkdir -p $edgeDir && chmod 0775)\n";
    }
} else {
    echo "$SKIP $edgeDir already exists\n";
}
if (is_dir($edgeDir) && !is_writable($edgeDir)) {
    @chmod($edgeDir, 0775);
    if (!is_writable($edgeDir)) {
        echo "$FAIL $edgeDir not writable — chmod 0775 manually\n";
    }
}

// ---------------------------------------------------------------------------
// STEP 3 — Diagnostics
// ---------------------------------------------------------------------------
echo "\n--- Diagnostics ---\n";
if (function_exists('apcu_enabled') && apcu_enabled()) {
    echo "$OK APCu tersedia & aktif — guard pakai in-memory (super cepat)\n";
} else {
    echo "$SKIP APCu TIDAK aktif — guard pakai file fallback.\n";
    echo "       Untuk performa terbaik di bawah serangan, aktifkan APCu via cPanel\n";
    echo "       (Select PHP Version -> Extensions -> apcu).\n";
}
if (function_exists('opcache_get_status')) {
    $s = @opcache_get_status(false);
    if ($s && !empty($s['opcache_enabled'])) {
        echo "$OK OPcache aktif\n";
    } else {
        echo "$SKIP OPcache TIDAK aktif — aktifkan untuk performa\n";
    }
}

// ---------------------------------------------------------------------------
// STEP 4 — Clear caches
// ---------------------------------------------------------------------------
foreach (['config.php', 'routes-v7.php', 'services.php', 'packages.php'] as $f) {
    $p = $BASE . '/bootstrap/cache/' . $f;
    if (is_file($p)) {
        @unlink($p);
        echo "$OK cleared $f\n";
    }
}

echo "\nDone.\n";
echo "Catatan: Aktifkan Cloudflare 'Under Attack Mode' atau Rate Limiting\n";
echo "         untuk pertahanan distributed-attack yang sesungguhnya.\n";
echo "\nIMPORTANT — HAPUS file ini dari public/ sekarang:\n";
echo "  rm " . __FILE__ . "\n";
