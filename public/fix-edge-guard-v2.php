<?php
/**
 * Slinkv hotfix — v0.4.12 — Edge guard denylist fix
 *
 * BUG (v0.4.11): regex `/[A-Za-z0-9_-]{1,32}/?` ikut menjebak path aplikasi
 * seperti /dashboard, /login, /admin, /pricing, dst. Akibatnya user
 * legit yang refresh dashboard kena 429 "Too Many Requests".
 *
 * FIX: ganti blok pre-boot edge guard di public/index.php dengan versi
 * baru yang:
 *   - Hanya menjaga path single-segment (tidak ada slash di tengah).
 *   - Punya denylist eksplisit path aplikasi (login, register, dashboard,
 *     admin, pricing, paket, faq, dll).
 *   - Reserved file (robots.txt, sitemap.xml, favicon.ico) tetap di-skip.
 *
 * Idempotent. Backup ditulis ke <file>.bak-edgeguard2.
 *
 * Usage:
 *   1. Upload ke folder public/ di server.
 *   2. Buka https://YOURDOMAIN/fix-edge-guard-v2.php?key=slinkv-fix-2026
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

echo "Slinkv hotfix v0.4.12 — edge guard denylist fix\n";
echo "BASE = $BASE\n\n";

// ---------------------------------------------------------------------------
// New guard block (final form — copy of public/index.php guard)
// ---------------------------------------------------------------------------
$newBlock = <<<'PHP'
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

    $segments = explode('/', trim($path, '/'));
    if (count($segments) !== 1) {
        return; // multi-segment -> never a /{slug} redirect
    }
    $first = $segments[0];
    if ($first === '') {
        return; // home page
    }

    static $appPaths = [
        'login', 'register', 'logout',
        'forgot-password', 'reset-password', 'password',
        'email', 'verify',
        'dashboard', 'admin', 'api',
        'pricing', 'paket', 'solusi', 'cara-kerja', 'artikel',
        'faq', 'tentang', 'kontak', 'terms', 'privacy',
        'refund-policy', 'acceptable-use-policy', 'abuse',
        'quick-shorten',
        'build', 'storage', 'vendor', 'css', 'js', 'img', 'images', 'assets',
        'index.php', 'robots.txt', 'sitemap.xml', 'favicon.ico',
        'site.webmanifest', 'apple-touch-icon.png',
        'install.php', 'debug.php', 'fix-bot-ddos.php', 'fix-clear-logs.php',
        'fix-edge-guard.php', 'fix-edge-guard-v2.php', 'fix-activate-user.php',
        'fix-mobile-sidebar.php', 'fix-runtime-dirs.php',
    ];
    if (in_array($first, $appPaths, true)) {
        return;
    }

    if (!preg_match('#^[A-Za-z0-9_\-]{1,32}$#', $first)) {
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

// ---------------------------------------------------------------------------
// STEP 1 — Patch public/index.php
// ---------------------------------------------------------------------------
$indexFile = $BASE . '/public/index.php';
if (!is_file($indexFile)) {
    echo "$FAIL public/index.php missing\n";
    exit;
}

$cur = (string) file_get_contents($indexFile);

if (str_contains($cur, 'v0.4.12')) {
    echo "$SKIP edge guard v0.4.12 sudah terpasang\n";
} else {
    // Cari blok lama (v0.4.11) untuk dihapus
    $startMarker = '// ─────────────────────────────────────────────────────────────────────────────' . "\n" . '// Slinkv pre-boot edge guard';
    $endMarker   = '})();' . "\n" . '// ─────────────────────────────────────────────────────────────────────────────';

    if (str_contains($cur, '// Slinkv pre-boot edge guard')) {
        $startPos = strpos($cur, '// ─────────────────────────────────────────────────────────────────────────────' . "\n" . '// Slinkv pre-boot edge guard');
        if ($startPos === false) {
            // Fallback: match start with looser marker
            $startPos = strpos($cur, '// Slinkv pre-boot edge guard');
            if ($startPos !== false) {
                // Walk back to the line containing the box ─
                $lineStart = strrpos(substr($cur, 0, $startPos), "\n// ─");
                if ($lineStart !== false) $startPos = $lineStart + 1;
            }
        }
        $endPos = strpos($cur, '})();' . "\n" . '// ─', $startPos ?: 0);
        if ($startPos === false || $endPos === false) {
            echo "$FAIL Tidak bisa menemukan batas blok edge guard lama. Patch manual.\n";
            exit;
        }
        // Hitung sampai akhir baris penutup ─
        $closeLineEnd = strpos($cur, "\n", $endPos + strlen('})();' . "\n" . '// ─'));
        if ($closeLineEnd === false) {
            echo "$FAIL Tidak bisa menentukan akhir blok edge guard lama.\n";
            exit;
        }
        $oldBlock = substr($cur, $startPos, $closeLineEnd - $startPos + 1);
        $patched  = str_replace($oldBlock, $newBlock . "\n", $cur);
    } else {
        // Belum ada guard sama sekali — sisipkan sebelum maintenance check
        $anchor = "// Determine if the application is in maintenance mode...";
        if (!str_contains($cur, $anchor)) {
            echo "$FAIL anchor maintenance-mode tidak ditemukan. Patch manual.\n";
            exit;
        }
        $patched = str_replace($anchor, $newBlock . "\n\n" . $anchor, $cur);
    }

    if (!is_file($indexFile . '.bak-edgeguard2')) {
        @copy($indexFile, $indexFile . '.bak-edgeguard2');
    }
    if (file_put_contents($indexFile, $patched) === false) {
        echo "$FAIL write failed: $indexFile\n";
        exit;
    }
    if (function_exists('opcache_invalidate')) @opcache_invalidate($indexFile, true);
    echo "$OK edge guard v0.4.12 installed (denylist app routes aktif)\n";
}

// ---------------------------------------------------------------------------
// STEP 2 — Clear semua block lama supaya user yang kena 429 langsung bebas
// ---------------------------------------------------------------------------
if (function_exists('apcu_enabled') && apcu_enabled()) {
    if (function_exists('apcu_clear_cache')) {
        @apcu_clear_cache();
        echo "$OK APCu cache cleared (semua block list di-reset)\n";
    }
}
$edgeDir = $BASE . '/storage/framework/cache/edge';
if (is_dir($edgeDir)) {
    $n = 0;
    foreach (glob($edgeDir . '/*') ?: [] as $f) {
        if (@unlink($f)) $n++;
    }
    echo "$OK edge file cache cleared ($n file)\n";
}

// ---------------------------------------------------------------------------
// STEP 3 — Clear bootstrap caches
// ---------------------------------------------------------------------------
foreach (['config.php', 'routes-v7.php', 'services.php', 'packages.php'] as $f) {
    $p = $BASE . '/bootstrap/cache/' . $f;
    if (is_file($p)) {
        @unlink($p);
        echo "$OK cleared $f\n";
    }
}

echo "\nDone.\n";
echo "Sekarang /dashboard, /login, /admin, /pricing, dll tidak akan kena 429.\n";
echo "\nIMPORTANT — HAPUS file ini dari public/ sekarang:\n";
echo "  rm " . __FILE__ . "\n";
