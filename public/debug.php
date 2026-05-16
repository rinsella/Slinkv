<?php
/**
 * SlinkV diagnostic — bootstraps the framework and reports why the app 500s.
 *
 * Safe to upload to production temporarily. Protected by a token so random
 * visitors can't view it. Delete this file once you've fixed the problem.
 *
 * Usage:
 *   https://YOURDOMAIN/debug.php?key=slinkv-debug-2026
 */

declare(strict_types=1);

if (function_exists('opcache_invalidate')) {
    @opcache_invalidate(__FILE__, true);
}

const DEBUG_TOKEN = 'slinkv-debug-2026';

@ini_set('display_errors', '1');
@ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if (($_GET['key'] ?? '') !== DEBUG_TOKEN) {
    http_response_code(403);
    exit('Forbidden. Append ?key=...');
}

header('Content-Type: text/plain; charset=utf-8');

$BASE = dirname(__DIR__);

echo "=== SlinkV diagnostic ===\n";
echo 'PHP: ' . PHP_VERSION . "\n";
echo 'SAPI: ' . PHP_SAPI . "\n";
echo 'CWD: ' . getcwd() . "\n";
echo 'BASE: ' . $BASE . "\n";
echo 'DOCROOT: ' . ($_SERVER['DOCUMENT_ROOT'] ?? '?') . "\n";
echo 'SCRIPT: ' . __FILE__ . "\n\n";

echo "--- File checks ---\n";
foreach ([
    'vendor/autoload.php',
    'bootstrap/app.php',
    '.env',
    'storage/installed.lock',
    'public/build/manifest.json',
    'storage/logs/laravel.log',
    'bootstrap/cache/services.php',
    'bootstrap/cache/packages.php',
    'bootstrap/cache/config.php',
    'bootstrap/cache/routes-v7.php',
] as $rel) {
    $p = $BASE . '/' . $rel;
    $exists = file_exists($p);
    echo str_pad($rel, 40) . ($exists ? ' EXISTS' : ' MISSING');
    if ($exists) {
        echo ' (' . (is_writable($p) ? 'w' : 'r') . ', ' . filesize($p) . 'b)';
    }
    echo "\n";
}

echo "\n--- Permissions ---\n";
foreach (['storage', 'storage/framework', 'storage/framework/views', 'storage/framework/cache', 'storage/logs', 'bootstrap/cache'] as $d) {
    $p = $BASE . '/' . $d;
    echo str_pad($d, 32) . (is_dir($p) ? 'dir' : 'NO') . ' ' . (is_writable($p) ? 'writable' : 'NOT-WRITABLE') . "\n";
}

echo "\n--- .env (sanitized) ---\n";
if (file_exists($BASE . '/.env')) {
    foreach (file($BASE . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (preg_match('/^([A-Z_]+)=/', $line, $m)) {
            $key = $m[1];
            if (in_array($key, ['DB_PASSWORD', 'MAIL_PASSWORD', 'APP_KEY'], true)) {
                $val = substr($line, strlen($key) + 1);
                echo $key . '=' . (strlen($val) > 0 ? '[' . strlen($val) . ' chars]' : '[empty]') . "\n";
            } else {
                echo $line . "\n";
            }
        }
    }
} else {
    echo "(no .env)\n";
}

echo "\n--- Laravel boot attempt ---\n";
try {
    require $BASE . '/vendor/autoload.php';
    /** @var \Illuminate\Foundation\Application $app */
    $app = require_once $BASE . '/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    echo "[OK] HTTP kernel built.\n";

    // Build a fake request for /
    $request = \Illuminate\Http\Request::create('/', 'GET');
    try {
        $response = $kernel->handle($request);
        echo "[OK] GET / returned HTTP " . $response->getStatusCode() . "\n";
        if ($response->getStatusCode() >= 500) {
            echo "Response body (first 4 KB):\n";
            echo substr((string)$response->getContent(), 0, 4096) . "\n";
        }
    } catch (\Throwable $e) {
        echo "[FAIL] Exception while handling GET /:\n";
        echo get_class($e) . ': ' . $e->getMessage() . "\n";
        echo 'at ' . $e->getFile() . ':' . $e->getLine() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
} catch (\Throwable $e) {
    echo "[FAIL] Could not even bootstrap Laravel:\n";
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
    echo 'at ' . $e->getFile() . ':' . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n--- storage/logs/laravel.log (last 80 lines) ---\n";
$log = $BASE . '/storage/logs/laravel.log';
if (file_exists($log)) {
    $lines = @file($log) ?: [];
    foreach (array_slice($lines, -80) as $l) {
        echo $l;
    }
} else {
    echo "(no laravel.log yet)\n";
}

echo "\n=== END ===\n";
