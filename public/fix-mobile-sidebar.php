<?php
/**
 * Slinkv mobile sidebar / mob-nav recovery script — v3 (idempotent, anchor-based).
 *
 * Patches base, dashboard, admin and public layouts so the mobile menus work
 * without any JS framework dependency. Smart enough to handle whatever state
 * the files are in (whether previous CDN fallback patches were applied or not).
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
const HANDLER_MARKER = 'slv-vanilla-handler-v2';

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

echo "BASE = $BASE\n\n";

function backup_and_write(string $path, string $newContent): bool
{
    global $OK, $FAIL;
    $bak = $path . '.bak-' . date('Ymd-His');
    @copy($path, $bak);
    if (@file_put_contents($path, $newContent) === false) {
        echo "$FAIL could not write $path (check chmod 664)\n";
        return false;
    }
    echo "$OK   wrote $path (backup: " . basename($bak) . ")\n";
    return true;
}

/**
 * Inject the vanilla-JS handler block into base.blade.php just before @stack('head').
 * Removes any older handler block first (idempotent).
 */
function patch_base_layout(string $path): void
{
    global $OK, $SKIP, $FAIL;

    if (!file_exists($path)) { echo "$FAIL not found: $path\n"; return; }
    $src = (string) file_get_contents($path);

    // Strip any existing handler block we previously injected (between marker comment and </script> closing of our handler).
    // Pattern: from "{{-- slv-vanilla-handler-v" ... up to first occurrence of "})();\n</script>\n" (our script's end).
    $src = preg_replace(
        '/\{\{--\s*slv-vanilla-handler-v\d+.*?<\/script>\s*/s',
        '',
        $src
    ) ?? $src;

    // Also strip the older Alpine CDN fallback block (the one we shipped in v0.4.4) — we'll re-add a cleaner version.
    $src = preg_replace(
        '/\{\{--\s*Fallback:\s*load Alpine\.js.*?<\/script>\s*/s',
        '',
        $src
    ) ?? $src;
    $src = preg_replace(
        '/\{\{--\s*Alpine CDN fallback\s*--\}\}\s*<script>\s*window\.addEventListener.*?<\/script>\s*/s',
        '',
        $src
    ) ?? $src;

    $block = <<<'BLADE'
{{-- Alpine CDN fallback (load only if Vite bundle failed to expose it) --}}
<script>
window.addEventListener('DOMContentLoaded', function () {
    if (typeof window.Alpine === 'undefined') {
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js';
        s.defer = true;
        document.head.appendChild(s);
    }
});
</script>
{{-- slv-vanilla-handler-v2 — bulletproof vanilla-JS toggles (sidebar + mob-nav) --}}
<style>
@media (max-width: 1023.98px) {
  body:not(.sidebar-open) .slv-sidebar { transform: translateX(-100%) !important; }
  body:not(.sidebar-open) .slv-sidebar-overlay { display: none !important; }
  body.sidebar-open .slv-sidebar { transform: translateX(0) !important; }
  body.sidebar-open .slv-sidebar-overlay { display: block !important; }
  body.sidebar-open { overflow: hidden; }
}
</style>
<script>
(function () {
  function ready(fn) {
    if (document.readyState !== 'loading') { fn(); }
    else { document.addEventListener('DOMContentLoaded', fn); }
  }
  ready(function () {
    document.addEventListener('click', function (e) {
      var open    = e.target.closest('[data-sidebar-open]');
      var close   = e.target.closest('[data-sidebar-close]');
      var over    = e.target.closest('[data-sidebar-overlay]');
      var mobBtn  = e.target.closest('[data-mobnav-toggle]');
      if (open)            { document.body.classList.add('sidebar-open');    e.preventDefault(); }
      if (close || over)   { document.body.classList.remove('sidebar-open'); e.preventDefault(); }
      if (mobBtn) {
        var panel = document.querySelector('[data-mobnav]') || document.getElementById('mob-nav');
        if (panel) { panel.classList.toggle('hidden'); e.preventDefault(); }
      }
    }, false);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        document.body.classList.remove('sidebar-open');
        var panel = document.querySelector('[data-mobnav]') || document.getElementById('mob-nav');
        if (panel && !panel.classList.contains('hidden')) { panel.classList.add('hidden'); }
      }
    });
  });
})();
</script>
BLADE;

    // Anchor: inject right before @stack('head').
    if (!str_contains($src, "@stack('head')")) {
        echo "$FAIL base.blade.php has no @stack('head') anchor — manual fix needed.\n";
        return;
    }
    $patched = str_replace("@stack('head')", $block . "\n@stack('head')", $src);

    if ($patched === $src) {
        echo "$SKIP base.blade.php replacement produced no change.\n";
        return;
    }

    if (backup_and_write($path, $patched)) {
        echo "$OK   base.blade.php :: vanilla handler installed\n";
    }
}

/**
 * Inject data-sidebar-* hooks into a dashboard/admin layout file.
 * Uses regex matching so it works regardless of class-attribute ordering.
 */
function patch_sidebar_layout(string $path, string $label): void
{
    global $OK, $SKIP, $FAIL;
    if (!file_exists($path)) { echo "$FAIL not found: $path\n"; return; }
    $src = (string) file_get_contents($path);
    $orig = $src;

    // 1. Overlay div: add `data-sidebar-overlay` + `slv-sidebar-overlay` class.
    if (!str_contains($src, 'data-sidebar-overlay')) {
        $src = preg_replace_callback(
            '/(<div\b[^>]*\bx-show="sidebar"[^>]*\bclass=")([^"]*)("[^>]*>)/',
            function ($m) {
                $classes = trim($m[2] . ' slv-sidebar-overlay');
                return $m[1] . $classes . '" data-sidebar-overlay' . substr($m[3], 1);
            },
            $src,
            1
        ) ?? $src;
    }

    // 2. Aside: add `data-sidebar` + `slv-sidebar` class + ensure `-translate-x-full lg:translate-x-0`.
    if (!str_contains($src, 'data-sidebar=') && !str_contains($src, ' data-sidebar ')) {
        $src = preg_replace_callback(
            '/(<aside\b[^>]*\bclass=")([^"]*)("[^>]*>)/',
            function ($m) {
                $classes = $m[2];
                if (!str_contains($classes, 'slv-sidebar')) { $classes .= ' slv-sidebar'; }
                if (!str_contains($classes, '-translate-x-full')) { $classes .= ' -translate-x-full lg:translate-x-0'; }
                return $m[1] . trim($classes) . '" data-sidebar' . substr($m[3], 1);
            },
            $src,
            1
        ) ?? $src;
    }

    // 3. Root x-data div: tag with data-sidebar-root.
    if (!str_contains($src, 'data-sidebar-root')) {
        $src = preg_replace(
            '/(<div\b[^>]*\bx-data="\{\s*sidebar\s*:\s*false\s*\}"[^>]*?)>/',
            '$1 data-sidebar-root>',
            $src,
            1
        ) ?? $src;
    }

    // 4. Close buttons: those with x-on:click="sidebar=false" but missing data-sidebar-close.
    $src = preg_replace_callback(
        '/<button\b([^>]*\bx-on:click="sidebar=false"[^>]*)>/',
        function ($m) {
            if (str_contains($m[1], 'data-sidebar-close')) { return $m[0]; }
            $attrs = $m[1];
            if (!preg_match('/\btype="button"/', $attrs)) { $attrs = ' type="button"' . $attrs; }
            return '<button' . $attrs . ' data-sidebar-close>';
        },
        $src
    ) ?? $src;

    // 5. Open buttons: those with x-on:click="sidebar=true" but missing data-sidebar-open.
    $src = preg_replace_callback(
        '/<button\b([^>]*\bx-on:click="sidebar=true"[^>]*)>/',
        function ($m) {
            if (str_contains($m[1], 'data-sidebar-open')) { return $m[0]; }
            $attrs = $m[1];
            if (!preg_match('/\btype="button"/', $attrs)) { $attrs = ' type="button"' . $attrs; }
            if (!preg_match('/\baria-label=/', $attrs))   { $attrs .= ' aria-label="Menu"'; }
            return '<button' . $attrs . ' data-sidebar-open>';
        },
        $src
    ) ?? $src;

    if ($src === $orig) {
        echo "$SKIP $label: nothing changed (already patched)\n";
        return;
    }
    if (backup_and_write($path, $src)) {
        echo "$OK   $label: data-sidebar-* hooks installed\n";
    }
}

/**
 * Patch the public layout: hamburger gets data-mobnav-toggle, mob-nav panel gets data-mobnav,
 * and the Dashboard auth-link becomes mobile-visible.
 */
function patch_public_layout(string $path): void
{
    global $OK, $SKIP, $FAIL;
    if (!file_exists($path)) { echo "$FAIL not found: $path\n"; return; }
    $src = (string) file_get_contents($path);
    $orig = $src;

    // Add data-mobnav-toggle to the lg:hidden hamburger button.
    if (!str_contains($src, 'data-mobnav-toggle')) {
        $src = preg_replace_callback(
            '/<button\b([^>]*\blg:hidden\b[^>]*aria-label="Menu"[^>]*)>/',
            function ($m) {
                $attrs = $m[1];
                if (!preg_match('/\btype="button"/', $attrs)) { $attrs = ' type="button"' . $attrs; }
                return '<button' . $attrs . ' data-mobnav-toggle>';
            },
            $src,
            1
        ) ?? $src;
    }

    // Add data-mobnav to the panel.
    if (!str_contains($src, 'data-mobnav') || strpos($src, 'data-mobnav') === strpos($src, 'data-mobnav-toggle')) {
        $src = preg_replace(
            '/<div\b([^>]*\bid="mob-nav"[^>]*)>/',
            '<div$1 data-mobnav>',
            $src,
            1
        ) ?? $src;
    }

    // Make Dashboard link visible on mobile when logged in (remove "hidden sm:inline" from that specific anchor).
    $src = preg_replace(
        '/(<a\b[^>]*route\(\'admin\.dashboard\'\)\s*:\s*route\(\'dashboard\.index\'\)\s*\}\}"\s*class=")hidden sm:inline\s+/',
        '$1',
        $src
    ) ?? $src;

    if ($src === $orig) {
        echo "$SKIP public.blade.php: nothing changed (already patched)\n";
        return;
    }
    if (backup_and_write($path, $src)) {
        echo "$OK   public.blade.php: mob-nav hooks + mobile Dashboard link installed\n";
    }
}

// ---- run patches ----
patch_base_layout($BASE . '/resources/views/layouts/base.blade.php');
patch_sidebar_layout($BASE . '/resources/views/layouts/dashboard.blade.php', 'dashboard.blade.php');
patch_sidebar_layout($BASE . '/resources/views/layouts/admin.blade.php', 'admin.blade.php');
patch_public_layout($BASE . '/resources/views/layouts/public.blade.php');

// ---- clear compiled blade views + bootstrap caches ----
$viewsDir = $BASE . '/storage/framework/views';
if (is_dir($viewsDir)) {
    $cleared = 0;
    foreach (glob($viewsDir . '/*.php') ?: [] as $f) {
        if (@unlink($f)) { $cleared++; }
    }
    echo "$OK   cleared $cleared compiled view file(s)\n";
} else {
    @mkdir($viewsDir, 0775, true);
    echo "$OK   created missing $viewsDir\n";
}
foreach (['config.php', 'routes-v7.php', 'events.php'] as $f) {
    $p = $BASE . '/bootstrap/cache/' . $f;
    if (file_exists($p)) {
        @unlink($p);
        echo "$OK   removed bootstrap/cache/$f\n";
    }
}

echo "\nDONE. Hard-reload your site on mobile (clear browser cache).\n";
echo "Test: homepage hamburger opens menu; dashboard/admin hamburger opens sidebar;\n";
echo "X / outside-tap / Esc closes; Dashboard link visible in homepage header when logged in.\n";
echo "REMEMBER to delete " . basename(__FILE__) . " from the server.\n";
