<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AdminAuditLogController extends Controller
{
    public function index(Request $request)
    {
        $q = AuditLog::with('admin:id,name,email');
        if ($a = $request->get('action')) $q->where('action', 'like', "%{$a}%");
        if ($e = $request->get('entity_type')) $q->where('entity_type', $e);
        if ($adminId = $request->get('admin_id')) $q->where('admin_id', $adminId);
        if ($from = $request->get('from')) $q->where('created_at', '>=', $from);
        if ($to = $request->get('to')) $q->where('created_at', '<=', $to);
        $logs = $q->latest()->paginate(40)->withQueryString();
        return view('admin.audit-logs', compact('logs'));
    }
}
