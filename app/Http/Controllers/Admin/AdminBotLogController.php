<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\BotRule;
use App\Models\ClickLog;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class AdminBotLogController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index(Request $request)
    {
        $q = ClickLog::with('shortLink:id,slug')->where(function ($w) {
            $w->where('is_bot', true)->orWhere('bot_score', '>=', 40);
        });
        if ($s = $request->get('q')) {
            $q->whereHas('shortLink', fn ($l) => $l->where('slug', 'like', "%{$s}%"));
        }
        $logs = $q->latest('clicked_at')->paginate(30)->withQueryString();
        return view('admin.bot-logs', compact('logs'));
    }

    public function blockIp(ClickLog $log)
    {
        if (empty($log->ip_hash)) {
            return back()->with('error', 'Log ini tidak punya IP hash.');
        }
        $ip = BlockedIp::updateOrCreate(
            ['ip_hash' => $log->ip_hash],
            ['reason' => "Blocked from bot log #{$log->id}", 'is_active' => true]
        );
        $this->audit->log('blocked_ip_create_from_log', $ip);
        return back()->with('success', 'IP diblokir.');
    }

    public function createUserAgentRule(ClickLog $log)
    {
        if (empty($log->user_agent)) {
            return back()->with('error', 'Log ini tidak punya user agent.');
        }
        $pattern = substr($log->user_agent, 0, 120);
        $rule = BotRule::create([
            'name'      => "UA from log #{$log->id}",
            'type'      => 'user_agent_contains',
            'pattern'   => $pattern,
            'score'     => 70,
            'is_active' => true,
        ]);
        $this->audit->log('bot_rule_create_from_log', $rule);
        return back()->with('success', 'Rule user-agent dibuat.');
    }
}
