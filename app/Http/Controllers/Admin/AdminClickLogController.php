<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\ClickLog;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class AdminClickLogController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index(Request $request)
    {
        $q = ClickLog::with('shortLink:id,slug,user_id');
        if ($t = $request->get('type')) {
            if ($t === 'bot') $q->where('is_bot', true);
            if ($t === 'human') $q->where('is_bot', false);
        }
        if ($cc = $request->get('country')) $q->where('country_code', strtoupper($cc));
        if ($s = $request->get('source')) $q->where('source_platform', $s);
        if ($slug = $request->get('short_link')) {
            $q->whereHas('shortLink', fn ($l) => $l->where('slug', 'like', "%{$slug}%"));
        }
        $logs = $q->latest('clicked_at')->paginate(30)->withQueryString();
        return view('admin.click-logs', compact('logs'));
    }

    public function blockIp(ClickLog $log)
    {
        if (empty($log->ip_hash)) {
            return back()->with('error', 'Log ini tidak punya IP hash.');
        }
        $ip = BlockedIp::updateOrCreate(
            ['ip_hash' => $log->ip_hash],
            ['reason' => "Blocked from click log #{$log->id}", 'is_active' => true]
        );
        $this->audit->log('blocked_ip_create_from_log', $ip);
        return back()->with('success', 'IP diblokir.');
    }
}
