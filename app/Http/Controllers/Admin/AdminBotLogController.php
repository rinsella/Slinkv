<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClickLog;
use Illuminate\Http\Request;

class AdminBotLogController extends Controller
{
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
}
