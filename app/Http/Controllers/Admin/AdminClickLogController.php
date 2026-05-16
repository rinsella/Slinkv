<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\ClickLog;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    /**
     * Bulk clear click logs.
     * scope = all | bots | older_30d
     * Uses chunked delete to avoid table-lock on large tables.
     */
    public function clear(Request $request)
    {
        $request->validate(['scope' => ['required', 'in:all,bots,older_30d']]);
        $scope = $request->input('scope');

        $base = DB::table('click_logs');
        $label = '';
        switch ($scope) {
            case 'bots':
                $base->where('is_bot', true);
                $label = 'bot click logs';
                break;
            case 'older_30d':
                $base->where('clicked_at', '<', now()->subDays(30));
                $label = 'click logs older than 30 days';
                break;
            case 'all':
            default:
                $label = 'ALL click logs';
                break;
        }

        // Chunked delete (max 5k rows per statement) so we never hold a long lock.
        $total = 0;
        do {
            $deleted = (clone $base)->limit(5000)->delete();
            $total += $deleted;
        } while ($deleted > 0);

        $this->audit->log('click_logs_cleared', null, null, [
            'scope' => $scope,
            'deleted' => $total,
        ]);

        return back()->with('success', "Berhasil hapus {$total} {$label}.");
    }
}
