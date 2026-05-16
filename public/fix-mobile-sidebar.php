<?php
/**
 * One-shot recovery: rewrite base + dashboard + admin Blade layouts so the
 * mobile sidebar works WITHOUT any JS framework. Uses a vanilla event
 * delegator + CSS so even if Alpine/Vite are completely broken on this host,
 * the menu open/close and overlay still function.
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

$ok   = '[OK]  ';
$skip = '[SKIP]';
$fail = '[FAIL]';

/**
 * @param string $path
 * @param array<int, array{find: string, replace: string, label: string}> $edits
 */
function patch_file(string $path, array $edits): void
{
    global $ok, $skip, $fail;
    if (!file_exists($path)) {
        echo "$fail not found: $path\n";
        return;
    }
    $orig = (string)file_get_contents($path);
    $new  = $orig;
    $hits = 0;
    foreach ($edits as $e) {
        if (str_contains($new, $e['replace']) && $e['replace'] !== $e['find']) {
            echo "$skip $path :: " . $e['label'] . " (already applied)\n";
            continue;
        }
        if (!str_contains($new, $e['find'])) {
            echo "$skip $path :: " . $e['label'] . " (needle not present)\n";
            continue;
        }
        $new = str_replace($e['find'], $e['replace'], $new);
        $hits++;
        echo "$ok   $path :: " . $e['label'] . "\n";
    }
    if ($hits === 0) {
        return;
    }
    $bak = $path . '.bak-' . date('Ymd-His');
    @copy($path, $bak);
    if (@file_put_contents($path, $new) === false) {
        echo "$fail could not write $path — check chmod 664\n";
        return;
    }
    echo "$ok   wrote $path  (backup: " . basename($bak) . ")\n";
}

echo "BASE = $BASE\n\n";

// -------- base.blade.php --------
patch_file($BASE . '/resources/views/layouts/base.blade.php', [
    [
        'label'   => 'inject Alpine CDN fallback + vanilla sidebar handler',
        'find'    => "@vite(['resources/css/app.css', 'resources/js/app.js'])\n@stack('head')",
        'replace' => "@vite(['resources/css/app.css', 'resources/js/app.js'])\n"
            . "{{-- Alpine CDN fallback --}}\n"
            . "<script>\nwindow.addEventListener('DOMContentLoaded', function () {\n"
            . "    if (typeof window.Alpine === 'undefined') {\n"
            . "        var s = document.createElement('script');\n"
            . "        s.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js';\n"
            . "        s.defer = true;\n"
            . "        document.head.appendChild(s);\n"
            . "    }\n});\n</script>\n"
            . "{{-- Bulletproof vanilla-JS sidebar toggle --}}\n"
            . "<style>\n@media (max-width: 1023.98px) {\n"
            . "  body:not(.sidebar-open) .slv-sidebar { transform: translateX(-100%) !important; }\n"
            . "  body:not(.sidebar-open) .slv-sidebar-overlay { display: none !important; }\n"
            . "  body.sidebar-open .slv-sidebar { transform: translateX(0) !important; }\n"
            . "  body.sidebar-open .slv-sidebar-overlay { display: block !important; }\n"
            . "  body.sidebar-open { overflow: hidden; }\n}\n</style>\n"
            . "<script>\n(function(){\n  function ready(fn){ if(document.readyState!=='loading'){fn();} else {document.addEventListener('DOMContentLoaded',fn);} }\n"
            . "  ready(function(){\n    document.addEventListener('click', function(e){\n"
            . "      var open=e.target.closest('[data-sidebar-open]');\n"
            . "      var close=e.target.closest('[data-sidebar-close]');\n"
            . "      var over=e.target.closest('[data-sidebar-overlay]');\n"
            . "      if(open){document.body.classList.add('sidebar-open');e.preventDefault();}\n"
            . "      if(close||over){document.body.classList.remove('sidebar-open');e.preventDefault();}\n"
            . "    }, false);\n"
            . "    document.addEventListener('keydown', function(e){ if(e.key==='Escape'){document.body.classList.remove('sidebar-open');} });\n"
            . "  });\n})();\n</script>\n"
            . "@stack('head')",
    ],
]);

// -------- dashboard.blade.php --------
patch_file($BASE . '/resources/views/layouts/dashboard.blade.php', [
    [
        'label'   => 'dashboard sidebar markup hooks',
        'find'    => "<div x-data=\"{ sidebar: false }\" class=\"min-h-full\">\n  <!-- Mobile overlay -->\n  <div x-show=\"sidebar\" x-transition.opacity class=\"fixed inset-0 bg-black/40 z-40 lg:hidden\" x-on:click=\"sidebar=false\" style=\"display:none\"></div>\n\n  <!-- Sidebar -->\n  <aside class=\"fixed inset-y-0 left-0 z-50 w-[240px] bg-white border-r border-line flex flex-col transform lg:transform-none transition-transform\"\n         :class=\"sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'\">",
        'replace' => "<div x-data=\"{ sidebar: false }\" class=\"min-h-full\" data-sidebar-root>\n  <!-- Mobile overlay -->\n  <div x-show=\"sidebar\" x-transition.opacity x-on:click=\"sidebar=false\" data-sidebar-overlay class=\"slv-sidebar-overlay fixed inset-0 bg-black/40 z-40 lg:hidden\" style=\"display:none\"></div>\n\n  <!-- Sidebar -->\n  <aside data-sidebar class=\"slv-sidebar fixed inset-y-0 left-0 z-50 w-[240px] bg-white border-r border-line flex flex-col transform lg:transform-none transition-transform -translate-x-full lg:translate-x-0\"\n         :class=\"sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'\">",
    ],
    [
        'label'   => 'dashboard X (close) button',
        'find'    => "      <button class=\"lg:hidden p-2\" x-on:click=\"sidebar=false\" aria-label=\"Tutup\">",
        'replace' => "      <button type=\"button\" class=\"lg:hidden p-2\" data-sidebar-close x-on:click=\"sidebar=false\" aria-label=\"Tutup\">",
    ],
    [
        'label'   => 'dashboard hamburger (open) button',
        'find'    => "      <button class=\"lg:hidden p-2 -ml-2\" x-on:click=\"sidebar=true\" aria-label=\"Menu\">",
        'replace' => "      <button type=\"button\" class=\"lg:hidden p-2 -ml-2\" data-sidebar-open x-on:click=\"sidebar=true\" aria-label=\"Menu\">",
    ],
]);

// -------- admin.blade.php --------
patch_file($BASE . '/resources/views/layouts/admin.blade.php', [
    [
        'label'   => 'admin sidebar markup hooks',
        'find'    => "<div x-data=\"{ sidebar:false }\" class=\"min-h-full\">\n  <div x-show=\"sidebar\" x-transition.opacity class=\"fixed inset-0 bg-black/40 z-40 lg:hidden\" x-on:click=\"sidebar=false\" style=\"display:none\"></div>\n  <aside class=\"fixed inset-y-0 left-0 z-50 w-[240px] bg-ink text-white flex flex-col transform lg:transform-none transition-transform\" :class=\"sidebar?'translate-x-0':'-translate-x-full lg:translate-x-0\">",
        'replace' => "<div x-data=\"{ sidebar:false }\" class=\"min-h-full\" data-sidebar-root>\n  <div x-show=\"sidebar\" x-transition.opacity data-sidebar-overlay class=\"slv-sidebar-overlay fixed inset-0 bg-black/40 z-40 lg:hidden\" x-on:click=\"sidebar=false\" style=\"display:none\"></div>\n  <aside data-sidebar class=\"slv-sidebar fixed inset-y-0 left-0 z-50 w-[240px] bg-ink text-white flex flex-col transform lg:transform-none transition-transform -translate-x-full lg:translate-x-0\" :class=\"sidebar?'translate-x-0':'-translate-x-full lg:translate-x-0'\">",
    ],
    [
        'label'   => 'admin X (close) button',
        'find'    => "      <button class=\"lg:hidden p-2\" x-on:click=\"sidebar=false\" aria-label=\"Tutup\">",
        'replace' => "      <button type=\"button\" class=\"lg:hidden p-2\" data-sidebar-close x-on:click=\"sidebar=false\" aria-label=\"Tutup\">",
    ],
    [
        'label'   => 'admin hamburger (open) button',
        'find'    => "      <button class=\"lg:hidden p-2 -ml-2\" x-on:click=\"sidebar=true\">",
        'replace' => "      <button type=\"button\" class=\"lg:hidden p-2 -ml-2\" data-sidebar-open x-on:click=\"sidebar=true\" aria-label=\"Menu\">",
    ],
]);

// Clear compiled Blade views and bootstrap caches so the patched layouts go live now.
$viewsDir = $BASE . '/storage/framework/views';
if (is_dir($viewsDir)) {
    $cleared = 0;
    foreach (glob($viewsDir . '/*.php') ?: [] as $f) {
        if (@unlink($f)) { $cleared++; }
    }
    echo "$ok   cleared $cleared compiled view file(s)\n";
} else {
    @mkdir($viewsDir, 0775, true);
    echo "$ok   created missing $viewsDir\n";
}

foreach (['config.php', 'routes-v7.php', 'events.php'] as $f) {
    $p = $BASE . '/bootstrap/cache/' . $f;
    if (file_exists($p)) {
        @unlink($p);
        echo "$ok   removed bootstrap/cache/$f\n";
    }
}

echo "\nDONE. Hard-reload your dashboard (Ctrl+Shift+R or mobile browser clear cache).\n";
echo "Now test on mobile: hamburger opens sidebar; X / outside-tap / Esc closes it.\n";
echo "REMEMBER to delete " . basename(__FILE__) . " from the server.\n";
