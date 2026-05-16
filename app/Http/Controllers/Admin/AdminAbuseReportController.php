<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbuseReport;
use App\Models\ShortLink;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class AdminAbuseReportController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index(Request $request)
    {
        $q = AbuseReport::with('shortLink');
        if ($s = $request->get('status')) $q->where('status', $s);
        $reports = $q->latest()->paginate(20)->withQueryString();
        return view('admin.abuse-reports.index', compact('reports'));
    }

    public function show(AbuseReport $abuse_report)
    {
        $abuse_report->load('shortLink.user');
        return view('admin.abuse-reports.show', ['report' => $abuse_report]);
    }

    public function review(Request $request, AbuseReport $abuse_report)
    {
        $request->validate(['admin_action' => ['nullable', 'string', 'max:1000']]);
        $abuse_report->update([
            'status' => 'reviewed',
            'admin_action' => $request->input('admin_action', $abuse_report->admin_action),
        ]);
        $this->audit->log('abuse_report_review', $abuse_report);
        return back()->with('success', 'Laporan ditandai sudah ditinjau.');
    }

    public function close(AbuseReport $abuse_report)
    {
        $abuse_report->update(['status' => 'closed']);
        $this->audit->log('abuse_report_close', $abuse_report);
        return back()->with('success', 'Laporan ditutup.');
    }

    public function disableLink(AbuseReport $abuse_report)
    {
        if ($abuse_report->short_link_id) {
            ShortLink::where('id', $abuse_report->short_link_id)->update(['is_active' => false, 'is_flagged' => true]);
        }
        $abuse_report->update(['status' => 'reviewed', 'admin_action' => 'Link dinonaktifkan']);
        $this->audit->log('abuse_report_disable_link', $abuse_report);
        return back()->with('success', 'Link terkait dinonaktifkan.');
    }

    public function destroy(AbuseReport $abuse_report)
    {
        $this->audit->log('abuse_report_delete', $abuse_report);
        $abuse_report->delete();
        return redirect()->route('admin.abuse-reports.index')->with('success', 'Laporan dihapus.');
    }
}
