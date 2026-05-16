<?php
/**
 * Slinkv hotfix — v0.4.10 — Fix activate-user toggle bug
 *
 * BUG: Pada /admin/users/{id}, tombol "Aktifkan User" tetap POST ke route
 * suspend, sehingga user yang sudah suspended tidak bisa diaktifkan
 * (klik aktifkan malah re-suspend dan menampilkan "User di-suspend.").
 *
 * FIX: Form action sekarang dinamis berdasarkan $user->status:
 *   - active     -> admin.users.suspend
 *   - suspended  -> admin.users.activate
 *
 * Usage:
 *   1. Upload ke folder public/ di server.
 *   2. Buka https://slinkv.net/fix-activate-user.php?key=slinkv-fix-2026
 *   3. Hapus file ini setelah semua [OK].
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

echo "Slinkv hotfix v0.4.10 — activate-user toggle\n";
echo "BASE = $BASE\n\n";

$file = $BASE . '/resources/views/admin/users/show.blade.php';
if (!is_file($file)) {
    echo "$FAIL missing: $file\n";
    exit;
}

$cur = (string) file_get_contents($file);

$old = "<form method=\"POST\" action=\"{{ route('admin.users.suspend', \$user) }}\" class=\"bg-white rounded-2xl border border-line p-5\">@csrf @method('PATCH')";
$new = "<form method=\"POST\" action=\"{{ route(\$user->status==='active' ? 'admin.users.suspend' : 'admin.users.activate', \$user) }}\" class=\"bg-white rounded-2xl border border-line p-5\">@csrf @method('PATCH')";

if (str_contains($cur, $new)) {
    echo "$SKIP already patched\n";
} elseif (!str_contains($cur, $old)) {
    echo "$FAIL anchor not found — file mungkin sudah dimodifikasi, periksa manual:\n  $file\n";
} else {
    if (!is_file($file . '.bak-activate')) @copy($file, $file . '.bak-activate');
    $patched = str_replace($old, $new, $cur);
    if (file_put_contents($file, $patched) === false) {
        echo "$FAIL write failed\n";
    } else {
        if (function_exists('opcache_invalidate')) @opcache_invalidate($file, true);
        echo "$OK patched show.blade.php\n";
    }
}

// Clear compiled views so Blade re-compiles
$viewsDir = $BASE . '/storage/framework/views';
if (is_dir($viewsDir)) {
    foreach (glob($viewsDir . '/*.php') ?: [] as $f) @unlink($f);
    echo "$OK compiled views cleared\n";
}

echo "\nDone.\n";
echo "IMPORTANT — DELETE this file from public/ now:\n";
echo "  rm " . __FILE__ . "\n";
