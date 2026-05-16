<?php

namespace App\Services;

use App\Models\ClickLog;
use App\Models\LinkDailyStat;
use App\Models\LinkHourlyStat;
use App\Models\ShortLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RedirectService
{
    public function __construct(
        private BotDetectionService $bot,
        private DeviceDetectionService $device,
        private SourceDetectionService $source,
        private GeoIpService $geo,
    ) {}

    /**
     * Process a slug click and return [redirectUrl, action, view, viewData].
     * action: redirected | blocked | fallback | expired | quota_exceeded | password_required | not_found
     */
    public function handle(string $slug, Request $request): array
    {
        $link = ShortLink::where('slug', $slug)->first();

        if (!$link) {
            return ['action' => 'not_found'];
        }

        if (!$link->is_active) {
            return ['action' => 'inactive', 'link' => $link];
        }

        if ($link->isExpired()) {
            $action = 'expired';
            $target = $link->fallback_url;
            $this->logClick($link, $request, $action, $target, ['expired']);
            if ($target) return ['action' => 'redirect', 'url' => $target];
            return ['action' => 'expired', 'link' => $link];
        }

        // Quota check (Free plan: 1000 clicks per link/month)
        $owner = $link->user;
        $plan = $owner?->effectivePlan();
        if ($plan && $plan->max_clicks_per_link) {
            $startMonth = now()->startOfMonth();
            $monthClicks = ClickLog::where('short_link_id', $link->id)
                ->where('clicked_at', '>=', $startMonth)
                ->count();
            if ($monthClicks >= (int) $plan->max_clicks_per_link) {
                $target = $link->fallback_url;
                $this->logClick($link, $request, 'quota_exceeded', $target, ['quota_exceeded']);
                if ($target) return ['action' => 'redirect', 'url' => $target];
                return ['action' => 'quota_exceeded', 'link' => $link];
            }
        }

        $ua = $request->userAgent();
        $ip = $request->ip();
        $referer = $request->headers->get('referer');
        $headers = [
            'accept-language' => $request->headers->get('accept-language'),
            'accept' => $request->headers->get('accept'),
        ];

        $deviceInfo = $this->device->detect($ua);
        $sourceName = $this->source->detect($referer, $request->query());
        $geoInfo = $this->geo->lookup($ip);

        // Bot detection
        $botResult = ['is_bot' => false, 'score' => 0, 'reasons' => []];
        if ($link->bot_protection_enabled) {
            $botResult = $this->bot->evaluate([
                'user_agent' => $ua,
                'ip' => $ip,
                'referer' => $referer,
                'headers' => $headers,
                'short_link_id' => $link->id,
            ]);
        }

        // Geo filter
        if ($link->geo_filter_enabled) {
            $cc = $geoInfo['country_code'];
            $allowed = $link->allowed_countries ?: [];
            $blocked = $link->blocked_countries ?: [];
            if (($allowed && $cc && !in_array($cc, $allowed, true))
                || ($blocked && $cc && in_array($cc, $blocked, true))) {
                $target = $link->fallback_url;
                $this->logClickFull($link, $request, 'blocked', $target, $deviceInfo, $sourceName, $geoInfo, ['geo_blocked'], false, 0);
                if ($target) return ['action' => 'redirect', 'url' => $target];
                return ['action' => 'blocked', 'link' => $link, 'reason' => 'geo'];
            }
        }

        // Device filter
        if ($link->device_filter !== 'all') {
            $cat = $this->device->deviceCategoryForFilter($deviceInfo['device']);
            if ($cat !== $link->device_filter) {
                $target = $link->fallback_url;
                $this->logClickFull($link, $request, 'blocked', $target, $deviceInfo, $sourceName, $geoInfo, ['device_blocked'], false, 0);
                if ($target) return ['action' => 'redirect', 'url' => $target];
                return ['action' => 'blocked', 'link' => $link, 'reason' => 'device'];
            }
        }

        if ($botResult['is_bot']) {
            $target = $link->fallback_url;
            $this->logClickFull($link, $request, 'blocked', $target, $deviceInfo, $sourceName, $geoInfo, $botResult['reasons'], true, $botResult['score']);
            if ($target) return ['action' => 'redirect', 'url' => $target];
            return ['action' => 'blocked', 'link' => $link, 'reason' => 'bot'];
        }

        $target = $link->destination_url;
        $this->logClickFull($link, $request, 'redirected', $target, $deviceInfo, $sourceName, $geoInfo, $botResult['reasons'], false, $botResult['score']);
        return ['action' => 'redirect', 'url' => $target];
    }

    private function logClick(ShortLink $link, Request $request, string $action, ?string $target, array $reasons): void
    {
        $deviceInfo = $this->device->detect($request->userAgent());
        $sourceName = $this->source->detect($request->headers->get('referer'), $request->query());
        $geoInfo = $this->geo->lookup($request->ip());
        $this->logClickFull($link, $request, $action, $target, $deviceInfo, $sourceName, $geoInfo, $reasons, false, 0);
    }

    private function logClickFull(ShortLink $link, Request $request, string $action, ?string $target, array $deviceInfo, string $sourceName, array $geoInfo, array $reasons, bool $isBot, int $score): void
    {
        try {
            $ip = $request->ip() ?? '';
            $now = now();

            ClickLog::create([
                'short_link_id' => $link->id,
                'user_id' => $link->user_id,
                'ip_hash' => $ip ? hash('sha256', $ip) : null,
                'user_agent' => $request->userAgent(),
                'referer' => $request->headers->get('referer'),
                'country_code' => $geoInfo['country_code'],
                'country_name' => $geoInfo['country_name'],
                'city' => $geoInfo['city'],
                'device_type' => $deviceInfo['device'],
                'browser' => $deviceInfo['browser'],
                'os' => $deviceInfo['os'],
                'source_platform' => $sourceName,
                'source_id' => $request->query('utm_source') ?? $request->query('source_id'),
                'is_bot' => $isBot,
                'bot_score' => $score,
                'bot_reasons' => $reasons,
                'action' => $action,
                'redirected_to' => $target,
                'clicked_at' => $now,
                'created_at' => $now,
            ]);

            DB::table('short_links')->where('id', $link->id)->update([
                'total_clicks' => DB::raw('total_clicks + 1'),
                'human_clicks' => DB::raw('human_clicks + ' . ($isBot ? 0 : 1)),
                'bot_clicks' => DB::raw('bot_clicks + ' . ($isBot ? 1 : 0)),
                'last_clicked_at' => $now,
            ]);

            // daily aggregate
            $date = $now->toDateString();
            LinkDailyStat::firstOrCreate(
                ['short_link_id' => $link->id, 'date' => $date],
                ['total_clicks' => 0, 'human_clicks' => 0, 'bot_clicks' => 0, 'unique_visitors' => 0]
            );
            DB::table('link_daily_stats')
                ->where('short_link_id', $link->id)->where('date', $date)
                ->update([
                    'total_clicks' => DB::raw('total_clicks + 1'),
                    'human_clicks' => DB::raw('human_clicks + ' . ($isBot ? 0 : 1)),
                    'bot_clicks' => DB::raw('bot_clicks + ' . ($isBot ? 1 : 0)),
                    'updated_at' => $now,
                ]);

            // hourly aggregate
            $hour = $now->copy()->startOfHour();
            LinkHourlyStat::firstOrCreate(
                ['short_link_id' => $link->id, 'datetime_hour' => $hour],
                ['total_clicks' => 0, 'human_clicks' => 0, 'bot_clicks' => 0]
            );
            DB::table('link_hourly_stats')
                ->where('short_link_id', $link->id)->where('datetime_hour', $hour)
                ->update([
                    'total_clicks' => DB::raw('total_clicks + 1'),
                    'human_clicks' => DB::raw('human_clicks + ' . ($isBot ? 0 : 1)),
                    'bot_clicks' => DB::raw('bot_clicks + ' . ($isBot ? 1 : 0)),
                    'updated_at' => $now,
                ]);
        } catch (\Throwable $e) {
            Log::warning('click_log_failed', ['error' => $e->getMessage(), 'link' => $link->id]);
        }
    }
}
