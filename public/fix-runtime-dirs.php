<?php
/**
 * One-shot recovery: create missing Laravel runtime directories.
 *
 * Symptom this fixes:
 *   InvalidArgumentException: Please provide a valid cache path.
 *
 * Usage: upload to public/ on the server alongside install.php, visit
 *   https://YOURDOMAIN/fix-runtime-dirs.php?key=slinkv-fix-2026
 * then DELETE this file.
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

$dirs = [
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/framework/testing',
    'storage/logs',
    'storage/app',
    'storage/app/public',
    'bootstrap/cache',
];

echo "BASE = $BASE\n\n";
foreach ($dirs as $rel) {
    $abs = $BASE . '/' . $rel;
    $existed = is_dir($abs);
    if (!$existed) {
        @mkdir($abs, 0775, true);
    }
    @chmod($abs, 0775);
    $now = is_dir($abs);
    $w   = $now && is_writable($abs);
    echo str_pad($rel, 42) . ' '
       . ($existed ? 'existed' : 'CREATED') . '  '
       . ($w ? 'writable' : 'NOT-writable') . "\n";
}

// Drop a .gitignore so the dirs survive future deploys.
foreach (['storage/framework/views', 'storage/framework/sessions', 'storage/framework/cache/data'] as $rel) {
    $f = $BASE . '/' . $rel . '/.gitignore';
    if (!file_exists($f)) {
        @file_put_contents($f, "*\n!.gitignore\n");
    }
}

// Clear bootstrap/cache so Laravel rebuilds it cleanly.
foreach (glob($BASE . '/bootstrap/cache/*.php') ?: [] as $f) {
    @unlink($f);
    echo "cleared: " . basename($f) . "\n";
}

echo "\nDONE. Now reload https://" . ($_SERVER['HTTP_HOST'] ?? 'YOURDOMAIN') . "/  and DELETE this file.\n";
