<?php
/**
 * Slinkv anti-DDoS / CPU survival hotfix — v0.4.7
 *
 * Applies the bot-flood / CPU-burn protection to an EXISTING Slinkv install
 * without reinstall. Idempotent — safe to run multiple times.
 *
 * What it does:
 *   1. Drops in app/Http/Middleware/RedirectRateLimit.php
 *      (edge throttle: 25 hits / 10s per IP → 5 min file-cache block,
 *       short-circuits BEFORE any DB query).
 *   2. Patches routes/web.php to attach the middleware to /{slug}.
 *   3. Patches app/Services/BotDetectionService.php to use the FILE cache
 *      store for all bot counters (instead of CACHE_STORE=database, which
 *      melts MySQL under attack) and auto-promotes flooding IPs to the
 *      edge-block list.
 *   4. Patches app/Services/RedirectService.php to SAMPLE click_logs +
 *      aggregate writes during bot bursts (3 first per IP/link/min, then
 *      1-in-50) — keeps logs readable and stops disk/MySQL meltdown.
 *   5. Patches resources/views/layouts/admin.blade.php so the mobile X /
 *      hamburger buttons are tappable on Android (larger hit area + SVG
 *      pointer-events:none).
 *   6. Ensures storage/framework/cache/data exists and is writable
 *      (file cache driver requirement).
 *   7. Clears bootstrap & view caches.
 *
 * Usage:
 *   1. Upload to public/ on the server.
 *   2. Visit https://YOURDOMAIN/fix-bot-ddos.php?key=slinkv-fix-2026
 *   3. DELETE this file when the report shows all [OK].
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

echo "Slinkv anti-DDoS hotfix v0.4.7\n";
echo "BASE = $BASE\n\n";

// ---------------------------------------------------------------------------
// helpers
// ---------------------------------------------------------------------------
function backup(string $file): void {
    if (is_file($file) && !is_file($file . '.bak-ddos')) {
        @copy($file, $file . '.bak-ddos');
    }
}
function writeIfChanged(string $file, string $new): string {
    global $OK, $SKIP, $FAIL;
    $cur = is_file($file) ? (string) file_get_contents($file) : '';
    if ($cur === $new) return $SKIP . " unchanged: " . basename($file);
    backup($file);
    if (file_put_contents($file, $new) === false) return $FAIL . " write: $file";
    if (function_exists('opcache_invalidate')) @opcache_invalidate($file, true);
    return $OK . " patched: " . basename($file);
}
function patch(string $file, string $oldNeedle, string $new, string $label): string {
    global $OK, $SKIP, $FAIL;
    if (!is_file($file)) return $FAIL . " missing: $file";
    $cur = (string) file_get_contents($file);
    if (str_contains($cur, $new)) return $SKIP . " $label (already applied)";
    if (!str_contains($cur, $oldNeedle)) return $FAIL . " $label (anchor not found — manual check needed)";
    $updated = str_replace($oldNeedle, $new, $cur);
    backup($file);
    if (file_put_contents($file, $updated) === false) return $FAIL . " $label (write failed)";
    if (function_exists('opcache_invalidate')) @opcache_invalidate($file, true);
    return $OK . " $label";
}

// ---------------------------------------------------------------------------
// STEP 1 — Drop in RedirectRateLimit middleware
// ---------------------------------------------------------------------------
$middlewarePath = $BASE . '/app/Http/Middleware/RedirectRateLimit.php';
$middlewareSrc = <<<'PHP'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ultra-cheap edge throttle for the public /{slug} redirect route.
 *
 * Goal: when bots/DDoS hit, short-circuit BEFORE any DB query, view render
 * or RedirectService work — so that floods do not melt the server.
 */
class RedirectRateLimit
{
    private const BURST_LIMIT = 25;
    private const BURST_WINDOW = 10;
    private const BLOCK_TTL = 300; // 5 minutes

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        if (!$ip) {
            return $next($request);
        }

        try {
            $cache = Cache::store('file');
        } catch (\Throwable $e) {
            return $next($request);
        }

        $hash = hash('sha256', $ip);
        $blockKey = "edge_block:{$hash}";
        $burstKey = "edge_burst:{$hash}";

        if ($cache->has($blockKey)) {
            return $this->throttleResponse();
        }

        $hits = 0;
        try {
            if ($cache->add($burstKey, 1, self::BURST_WINDOW)) {
                $hits = 1;
            } else {
                $hits = (int) $cache->increment($burstKey);
            }
        } catch (\Throwable $e) {
            return $next($request);
        }

        if ($hits > self::BURST_LIMIT) {
            try {
                $cache->put($blockKey, 1, self::BLOCK_TTL);
                Log::warning('edge_rate_limit_blocked', [
                    'ip_hash' => substr($hash, 0, 12),
                    'hits' => $hits,
                    'window' => self::BURST_WINDOW,
                    'ttl' => self::BLOCK_TTL,
                ]);
            } catch (\Throwable $e) {
                // ignore
            }
            return $this->throttleResponse();
        }

        return $next($request);
    }

    private function throttleResponse(): Response
    {
        return response('Too Many Requests', 429, [
            'Retry-After' => (string) self::BLOCK_TTL,
            'Cache-Control' => 'no-store',
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
PHP;

@mkdir(dirname($middlewarePath), 0755, true);
echo writeIfChanged($middlewarePath, $middlewareSrc) . "\n";

// ---------------------------------------------------------------------------
// STEP 2 — Wire middleware in routes/web.php
// ---------------------------------------------------------------------------
$routesPath = $BASE . '/routes/web.php';
echo patch(
    $routesPath,
    "Route::get('/{slug}', RedirectController::class)\n    ->where('slug', '[A-Za-z0-9_-]{1,32}')\n    ->name('redirect');",
    "Route::get('/{slug}', RedirectController::class)\n    ->middleware(\\App\\Http\\Middleware\\RedirectRateLimit::class)\n    ->where('slug', '[A-Za-z0-9_-]{1,32}')\n    ->name('redirect');",
    'routes/web.php (attach edge throttle to /{slug})'
) . "\n";

// ---------------------------------------------------------------------------
// STEP 3 — Patch BotDetectionService to use file cache + auto-escalate to edge_block
// ---------------------------------------------------------------------------
$botPath = $BASE . '/app/Services/BotDetectionService.php';

if (!is_file($botPath)) {
    echo "$FAIL missing $botPath\n";
} else {
    $orig = (string) file_get_contents($botPath);

    if (str_contains($orig, 'private function fast()')) {
        echo "$SKIP BotDetectionService (already patched)\n";
    } else {
        $new = $orig;

        // 3a) replace header (add fast() helper + CacheRepository import)
        $headerOld = "<?php\n\nnamespace App\\Services;\n\nuse App\\Models\\BlockedIp;\nuse App\\Models\\BotRule;\nuse Illuminate\\Support\\Facades\\Cache;\n\nclass BotDetectionService\n{\n    private array \$crawlerSignatures = [";
        $headerNew = "<?php\n\nnamespace App\\Services;\n\nuse App\\Models\\BlockedIp;\nuse App\\Models\\BotRule;\nuse Illuminate\\Contracts\\Cache\\Repository as CacheRepository;\nuse Illuminate\\Support\\Facades\\Cache;\n\nclass BotDetectionService\n{\n    private function fast(): CacheRepository\n    {\n        try { return Cache::store('file'); } catch (\\Throwable \$e) { return Cache::store(); }\n    }\n\n    private array \$crawlerSignatures = [";
        if (str_contains($new, $headerOld)) {
            $new = str_replace($headerOld, $headerNew, $new);
        } else {
            echo "$FAIL BotDetectionService header anchor not found\n";
        }

        // 3b) inject $fast = $this->fast(); at top of evaluate()
        $evalOld = "\$referer = \$ctx['referer'] ?? '';\n\n        // 1. Empty UA";
        $evalNew = "\$referer = \$ctx['referer'] ?? '';\n        \$fast = \$this->fast();\n\n        // 1. Empty UA";
        $new = str_replace($evalOld, $evalNew, $new);

        // 3c) swap Cache:: calls for $fast-> inside evaluate()
        // We do targeted replacements only on the cache lines that exist in v0.4.6.
        $pairs = [
            // rate per link
            ['$hits = (int) Cache::get($key, 0);',     '$hits = (int) $fast->get($key, 0);'],
            ['Cache::put($key, $hits + 1, 60);',       '$fast->put($key, $hits + 1, 60);'],
            // global rate
            ['$g = (int) Cache::get($globalKey, 0);',  '$g = (int) $fast->get($globalKey, 0);'],
            ['Cache::put($globalKey, $g + 1, 60);',    '$fast->put($globalKey, $g + 1, 60);'],
            // UA burst
            ['$uaHits = (int) Cache::get($uaKey, 0);', '$uaHits = (int) $fast->get($uaKey, 0);'],
            ['Cache::put($uaKey, $uaHits + 1, 60);',   '$fast->put($uaKey, $uaHits + 1, 60);'],
            // ip block lookup
            ['$blocked = Cache::remember("blocked_ip:{$hash}", 300, fn () =>',
             '$blocked = $fast->remember("blocked_ip:{$hash}", 300, fn () =>'],
            // bot rules
            ["\$rules = Cache::remember('bot_rules:active', 300, fn () =>",
             "\$rules = \$fast->remember('bot_rules:active', 300, fn () =>"],
        ];
        foreach ($pairs as [$a, $b]) {
            if (str_contains($new, $a)) {
                $new = str_replace($a, $b, $new);
            }
        }

        // 3d) add escalation block right before `return [` final array
        $tailOld = "        \$score = min(100, \$score);\n        \$isBot = \$score >= 70;\n\n        return [";
        $tailNew = "        \$score = min(100, \$score);\n        \$isBot = \$score >= 70;\n\n        if (\$isBot && \$ip && (\n                in_array('rate_per_link', \$reasons, true)\n                || in_array('rate_global', \$reasons, true)\n                || in_array('ua_burst', \$reasons, true)\n                || in_array('ip_blocked', \$reasons, true)\n            )) {\n            try { \$fast->put('edge_block:' . hash('sha256', \$ip), 1, 300); } catch (\\Throwable \$e) {}\n        }\n\n        return [";
        if (str_contains($new, $tailOld)) {
            $new = str_replace($tailOld, $tailNew, $new);
        }

        if ($new !== $orig) {
            backup($botPath);
            file_put_contents($botPath, $new);
            if (function_exists('opcache_invalidate')) @opcache_invalidate($botPath, true);
            echo "$OK BotDetectionService patched (file-cache + edge_block escalation)\n";
        } else {
            echo "$FAIL BotDetectionService not modified\n";
        }
    }
}

// ---------------------------------------------------------------------------
// STEP 4 — Patch RedirectService to sample bot logs
// ---------------------------------------------------------------------------
$redirectSvcPath = $BASE . '/app/Services/RedirectService.php';
if (!is_file($redirectSvcPath)) {
    echo "$FAIL missing $redirectSvcPath\n";
} else {
    $orig = (string) file_get_contents($redirectSvcPath);
    if (str_contains($orig, '$shouldInsertRow')) {
        echo "$SKIP RedirectService (already patched)\n";
    } else {
        $needle = "            \$ip = \$request->ip() ?? '';\n            \$now = now();\n            \$countsClick = !in_array(\$action, ['password_required', 'password_failed'], true);\n\n            ClickLog::create([";
        $replacement = "            \$ip = \$request->ip() ?? '';\n            \$now = now();\n            \$countsClick = !in_array(\$action, ['password_required', 'password_failed'], true);\n\n            \$shouldInsertRow = true;\n            if (\$isBot && \$ip) {\n                \$burstKey = 'log_sample:' . hash('sha256', \$ip) . ':' . \$link->id;\n                try {\n                    \$fast = \\Illuminate\\Support\\Facades\\Cache::store('file');\n                    \$count = (int) \$fast->get(\$burstKey, 0);\n                    \$fast->put(\$burstKey, \$count + 1, 60);\n                    if (\$count >= 3 && (\$count % 50) !== 0) {\n                        \$shouldInsertRow = false;\n                    }\n                } catch (\\Throwable \$e) {}\n            }\n\n            if (\$shouldInsertRow) {\n            ClickLog::create([";
        if (str_contains($orig, $needle)) {
            $patched = str_replace($needle, $replacement, $orig);
            // close the if-block after the create([...]) statement
            // find the original closing of ClickLog::create([...]);
            $closeOld = "                'clicked_at' => \$now,\n                'created_at' => \$now,\n            ]);\n\n            if (!\$countsClick) {";
            $closeNew = "                'clicked_at' => \$now,\n                'created_at' => \$now,\n            ]);\n            }\n\n            if (!\$countsClick) {\n                return;\n            }\n\n            if (!\$shouldInsertRow) {";
            if (str_contains($patched, $closeOld)) {
                // We need to be careful: the original has "if (!\$countsClick) {\n                return;\n            }" already.
                // Strategy: replace the whole region.
                $regionOld = "                'clicked_at' => \$now,\n                'created_at' => \$now,\n            ]);\n\n            if (!\$countsClick) {\n                return;\n            }";
                $regionNew = "                'clicked_at' => \$now,\n                'created_at' => \$now,\n            ]);\n            }\n\n            if (!\$countsClick) {\n                return;\n            }\n\n            if (!\$shouldInsertRow) {\n                return;\n            }";
                if (str_contains($patched, $regionOld)) {
                    $patched = str_replace($regionOld, $regionNew, $patched);
                    backup($redirectSvcPath);
                    file_put_contents($redirectSvcPath, $patched);
                    if (function_exists('opcache_invalidate')) @opcache_invalidate($redirectSvcPath, true);
                    echo "$OK RedirectService patched (bot-log sampling)\n";
                } else {
                    echo "$FAIL RedirectService region anchor not found (rolled back)\n";
                }
            } else {
                echo "$FAIL RedirectService close anchor not found\n";
            }
        } else {
            echo "$FAIL RedirectService open anchor not found\n";
        }
    }
}

// ---------------------------------------------------------------------------
// STEP 5 — Patch admin layout mobile buttons
// ---------------------------------------------------------------------------
$adminLayoutPath = $BASE . '/resources/views/layouts/admin.blade.php';
echo patch(
    $adminLayoutPath,
    "<button type=\"button\" class=\"lg:hidden p-2\" data-sidebar-close x-on:click=\"sidebar=false\" aria-label=\"Tutup\">\n        <svg width=\"20\" height=\"20\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\"><path d=\"M6 6l12 12M18 6L6 18\"/></svg>\n      </button>",
    "<button type=\"button\" class=\"lg:hidden p-3 -mr-2 inline-flex items-center justify-center\" data-sidebar-close x-on:click=\"sidebar=false\" aria-label=\"Tutup\">\n        <svg width=\"22\" height=\"22\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" style=\"pointer-events:none\"><path d=\"M6 6l12 12M18 6L6 18\"/></svg>\n      </button>",
    'admin.blade.php close button'
) . "\n";

echo patch(
    $adminLayoutPath,
    "<button type=\"button\" class=\"lg:hidden p-2 -ml-2\" data-sidebar-open x-on:click=\"sidebar=true\" aria-label=\"Menu\">\n        <svg width=\"22\" height=\"22\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\"><path d=\"M4 6h16M4 12h16M4 18h16\"/></svg>\n      </button>",
    "<button type=\"button\" class=\"lg:hidden p-3 -ml-2 inline-flex items-center justify-center\" data-sidebar-open x-on:click=\"sidebar=true\" aria-label=\"Menu\">\n        <svg width=\"22\" height=\"22\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" style=\"pointer-events:none\"><path d=\"M4 6h16M4 12h16M4 18h16\"/></svg>\n      </button>",
    'admin.blade.php open button'
) . "\n";

// ---------------------------------------------------------------------------
// STEP 6 — Ensure file-cache dir exists & writable
// ---------------------------------------------------------------------------
$cacheDir = $BASE . '/storage/framework/cache/data';
if (!is_dir($cacheDir)) {
    if (@mkdir($cacheDir, 0775, true)) {
        echo "$OK created $cacheDir\n";
    } else {
        echo "$FAIL could not create $cacheDir\n";
    }
} else {
    echo "$SKIP $cacheDir already exists\n";
}
if (is_dir($cacheDir) && !is_writable($cacheDir)) {
    @chmod($cacheDir, 0775);
    if (!is_writable($cacheDir)) {
        echo "$FAIL $cacheDir not writable — chmod to 0775 manually\n";
    } else {
        echo "$OK $cacheDir now writable\n";
    }
}

// ---------------------------------------------------------------------------
// STEP 7 — Clear bootstrap/view caches so changes take effect immediately
// ---------------------------------------------------------------------------
$clears = [
    $BASE . '/bootstrap/cache/config.php',
    $BASE . '/bootstrap/cache/routes-v7.php',
    $BASE . '/bootstrap/cache/services.php',
    $BASE . '/bootstrap/cache/packages.php',
];
foreach ($clears as $f) {
    if (is_file($f)) {
        if (@unlink($f)) echo "$OK cleared " . basename($f) . "\n";
        else echo "$FAIL could not clear " . basename($f) . "\n";
    }
}
$viewsDir = $BASE . '/storage/framework/views';
if (is_dir($viewsDir)) {
    foreach (glob($viewsDir . '/*.php') ?: [] as $f) @unlink($f);
    echo "$OK compiled views cleared\n";
}

echo "\nDone.\n";
echo "IMPORTANT — DELETE this file from public/ now:\n";
echo "  rm " . __FILE__ . "\n";
