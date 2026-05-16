<?php
/**
 * One-shot recovery: patch base layout to fall back to Alpine.js CDN if the
 * Vite-built JS bundle isn't exposing window.Alpine on production.
 *
 * Symptom this fixes: mobile sidebar opens but the X (close) button does
 * nothing, the dark overlay never appears, and clicking outside doesn't
 * dismiss the menu. Root cause is Alpine.js failing to load (LiteSpeed
 * cache, mod_pagespeed, aggressive CDN, etc.) — without Alpine all the
 * x-on:click handlers are dead.
 *
 * Usage:
 *   Upload to public/ on the server, visit
 *     https://YOURDOMAIN/fix-mobile-sidebar.php?key=slinkv-fix-2026
 *   then DELETE this file.
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
$layout = $BASE . '/resources/views/layouts/base.blade.php';

echo "BASE   = $BASE\n";
echo "LAYOUT = $layout\n\n";

if (!file_exists($layout)) {
    echo "[FAIL] Layout file not found. Aborting.\n";
    exit(1);
}

$contents = (string)file_get_contents($layout);

if (str_contains($contents, 'cdn.jsdelivr.net/npm/alpinejs')) {
    echo "[SKIP] Alpine CDN fallback already present in base.blade.php — nothing to inject.\n";
} else {
    $needle = "@vite(['resources/css/app.css', 'resources/js/app.js'])";
    if (!str_contains($contents, $needle)) {
        echo "[FAIL] Could not find @vite directive in base.blade.php. Aborting (no changes written).\n";
        exit(1);
    }

    $fallback = $needle . "\n"
        . "{{-- Fallback: load Alpine.js from CDN if the Vite-built bundle failed to expose it.\n"
        . "     Some shared hosts (LiteSpeed cache, mod_pagespeed, aggressive CDNs) corrupt or\n"
        . "     drop the bundled JS — without Alpine the mobile sidebar gets stuck open. --}}\n"
        . "<script>\n"
        . "window.addEventListener('DOMContentLoaded', function () {\n"
        . "    if (typeof window.Alpine === 'undefined') {\n"
        . "        var s = document.createElement('script');\n"
        . "        s.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js';\n"
        . "        s.defer = true;\n"
        . "        document.head.appendChild(s);\n"
        . "    }\n"
        . "});\n"
        . "</script>";

    $new = str_replace($needle, $fallback, $contents);
    if ($new === $contents) {
        echo "[FAIL] str_replace produced no change. Aborting.\n";
        exit(1);
    }

    // Backup original.
    $backup = $layout . '.bak-' . date('Ymd-His');
    @copy($layout, $backup);
    echo "[OK]   Backup written: " . basename($backup) . "\n";

    if (@file_put_contents($layout, $new) === false) {
        echo "[FAIL] Could not write patched layout. Check file permissions (chmod 664 base.blade.php).\n";
        exit(1);
    }
    echo "[OK]   Patched base.blade.php with Alpine CDN fallback (" . strlen($new) . " bytes).\n";
}

// Clear compiled Blade views so Laravel re-renders with the patched layout.
$viewsDir = $BASE . '/storage/framework/views';
if (is_dir($viewsDir)) {
    $cleared = 0;
    foreach (glob($viewsDir . '/*.php') ?: [] as $f) {
        if (@unlink($f)) {
            $cleared++;
        }
    }
    echo "[OK]   Cleared $cleared compiled view file(s) from storage/framework/views/.\n";
} else {
    @mkdir($viewsDir, 0775, true);
    echo "[OK]   Created missing $viewsDir\n";
}

// Clear bootstrap cache too — config and routes may reference old view paths.
foreach (glob($BASE . '/bootstrap/cache/*.php') ?: [] as $f) {
    if (in_array(basename($f), ['services.php', 'packages.php'], true)) {
        continue; // these are composer-managed, leave alone
    }
    @unlink($f);
    echo "[OK]   Removed bootstrap/cache/" . basename($f) . "\n";
}

echo "\nDONE. Hard-reload (Ctrl+Shift+R) https://" . ($_SERVER['HTTP_HOST'] ?? 'YOURDOMAIN') . "/dashboard then test the mobile sidebar X button.\nREMEMBER to delete this file from the server.\n";
