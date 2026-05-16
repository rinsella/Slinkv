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
 *
 * Implementation notes:
 *  - Always uses the FILE cache driver, regardless of CACHE_STORE.
 *    The default in this app is `database`, which means every Cache::put
 *    hits MySQL — fatal under attack. File cache writes hit local disk,
 *    have no DB roundtrip, and survive shared-host environments.
 *  - Two tiers:
 *      1. Per-IP burst counter (window 10s). If exceeded → IP is "edge_blocked".
 *      2. Edge-block check at the very top: blocked IPs get an immediate
 *         429 with Retry-After. No DB queries, no view, no log writes.
 *  - Counters are keyed by sha256(ip) so the IP itself is never written to disk
 *    in plain form (consistent with the rest of the codebase).
 */
class RedirectRateLimit
{
    /** Hits allowed per IP within the burst window before we hard-throttle. */
    private const BURST_LIMIT = 25;

    /** Burst window in seconds. */
    private const BURST_WINDOW = 10;

    /** How long an IP stays edge-blocked once it trips the limit (seconds). */
    private const BLOCK_TTL = 300; // 5 minutes

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        if (!$ip) {
            return $next($request);
        }

        // Use file cache explicitly — never touch the DB cache store here.
        try {
            $cache = Cache::store('file');
        } catch (\Throwable $e) {
            // If file store is unavailable for any reason, fall through quietly
            // so legitimate traffic still works.
            return $next($request);
        }

        $hash = hash('sha256', $ip);
        $blockKey = "edge_block:{$hash}";
        $burstKey = "edge_burst:{$hash}";

        // 1. Fast path: already edge-blocked → return 429 immediately.
        if ($cache->has($blockKey)) {
            return $this->throttleResponse();
        }

        // 2. Increment burst counter atomically.
        $hits = 0;
        try {
            // Cache::add returns true only on first insert; ensures TTL is set once.
            if ($cache->add($burstKey, 1, self::BURST_WINDOW)) {
                $hits = 1;
            } else {
                $hits = (int) $cache->increment($burstKey);
                // increment() does not refresh TTL — that is intentional;
                // it gives us a true sliding-ish window of BURST_WINDOW seconds.
            }
        } catch (\Throwable $e) {
            // Cache failure must never break the redirect for real users.
            return $next($request);
        }

        if ($hits > self::BURST_LIMIT) {
            try {
                $cache->put($blockKey, 1, self::BLOCK_TTL);
                // Log once per block event (not per request) so attacks are visible
                // in storage/logs without filling the disk during a flood.
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
        // Minimal body — keep payload tiny under attack.
        return response('Too Many Requests', 429, [
            'Retry-After' => (string) self::BLOCK_TTL,
            'Cache-Control' => 'no-store',
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
