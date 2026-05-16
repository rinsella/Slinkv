<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClickLog;
use Illuminate\Http\Request;

class AdminClickLogController extends Controller
{
    public function index(Request $request)
    {
        $q = ClickLog::with('shortLink:id,slug,user_id');
        if ($t = $request->get('type')) {
            if ($t === 'bot') $q->where('is_bot', true);
            if ($t === 'human') $q->where('is_bot', false);
        }
        if ($cc = $request->get('country')) $q->where('country_code', strtoupper($cc));
        if ($s = $request->get('source')) $q->where('source_platform', $s);
        $logs = $q->latest('clicked_at')->paginate(30)->withQueryString();
        return view('admin.click-logs', compact('logs'));
    }
}
