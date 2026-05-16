<?php

namespace App\Services;

use App\Models\BlockedIp;
use App\Models\BotRule;
use Illuminate\Support\Facades\Cache;

class BotDetectionService
{
    private array $crawlerSignatures = [
        'bot', 'crawler', 'spider', 'slurp', 'crawling',
        'googlebot', 'bingbot', 'yandexbot', 'duckduckbot', 'baiduspider',
        'ahrefsbot', 'semrushbot', 'mj12bot', 'dotbot', 'petalbot',
        'facebookexternalhit', 'twitterbot', 'linkedinbot', 'discordbot', 'telegrambot',
        'headlesschrome', 'phantomjs', 'puppeteer', 'selenium',
    ];

    private array $cliSignatures = [
        'curl/', 'wget/', 'python-requests', 'python-urllib', 'go-http-client', 'libwww-perl',
    ];

    public function evaluate(array $ctx): array
    {
        $score = 0;
        $reasons = [];
        $ua = strtolower($ctx['user_agent'] ?? '');
        $ip = $ctx['ip'] ?? null;
        $linkId = $ctx['short_link_id'] ?? 0;
        $headers = $ctx['headers'] ?? [];
        $referer = $ctx['referer'] ?? '';

        // 1. Empty UA
        if ($ua === '') { $score += 50; $reasons[] = 'empty_user_agent'; }

        // 2. Crawler signatures
        foreach ($this->crawlerSignatures as $sig) {
            if (str_contains($ua, $sig)) { $score += 70; $reasons[] = "ua_contains:{$sig}"; break; }
        }

        // 3. CLI tools
        foreach ($this->cliSignatures as $sig) {
            if (str_contains($ua, $sig)) { $score += 70; $reasons[] = "cli_tool:{$sig}"; break; }
        }

        // 4. Postman / axios default
        if (str_contains($ua, 'postmanruntime')) { $score += 50; $reasons[] = 'postman'; }
        if (str_contains($ua, 'axios/')) { $score += 40; $reasons[] = 'axios_default'; }

        // 5. Missing Accept-Language
        if (empty($headers['accept-language'] ?? null)) { $score += 20; $reasons[] = 'no_accept_language'; }

        // 6. Suspicious referer (data:, javascript:)
        if ($referer && preg_match('/^(data|javascript|file):/i', $referer)) {
            $score += 30; $reasons[] = 'suspicious_referer';
        }

        // 7. Rate per IP -> link in last 60s
        if ($ip && $linkId) {
            $key = "bot:rate:{$linkId}:" . md5($ip);
            $hits = (int) Cache::get($key, 0);
            Cache::put($key, $hits + 1, 60);
            if ($hits >= 8) { $score += 50; $reasons[] = 'rate_per_link'; }

            // 8. Cross-link spam by same IP in 60s
            $globalKey = 'bot:rate:any:' . md5($ip);
            $g = (int) Cache::get($globalKey, 0);
            Cache::put($globalKey, $g + 1, 60);
            if ($g >= 30) { $score += 40; $reasons[] = 'rate_global'; }
        }

        // 9. Same UA spam
        if ($ua) {
            $uaKey = 'bot:ua:' . md5($ua);
            $uaHits = (int) Cache::get($uaKey, 0);
            Cache::put($uaKey, $uaHits + 1, 60);
            if ($uaHits >= 50) { $score += 30; $reasons[] = 'ua_burst'; }
        }

        // 10. IP blocklist
        if ($ip) {
            $hash = hash('sha256', $ip);
            $blocked = Cache::remember("blocked_ip:{$hash}", 300, fn () =>
                BlockedIp::where('ip_hash', $hash)->where('is_active', true)
                    ->where(function ($q) { $q->whereNull('expires_at')->orWhere('expires_at', '>', now()); })
                    ->exists()
            );
            if ($blocked) { $score += 100; $reasons[] = 'ip_blocked'; }
        }

        // 11. Custom bot rules from admin
        $rules = Cache::remember('bot_rules:active', 300, fn () =>
            BotRule::where('is_active', true)->get()->all()
        );
        foreach ($rules as $rule) {
            if ($rule->type === 'user_agent_contains' && $rule->pattern && str_contains($ua, strtolower($rule->pattern))) {
                $score += $rule->score; $reasons[] = "rule:{$rule->name}";
            }
        }

        $score = min(100, $score);
        $isBot = $score >= 70;

        return [
            'is_bot' => $isBot,
            'score' => $score,
            'reasons' => $reasons,
            'classification' => $score >= 70 ? 'bot' : ($score >= 40 ? 'suspicious' : 'human'),
        ];
    }
}
