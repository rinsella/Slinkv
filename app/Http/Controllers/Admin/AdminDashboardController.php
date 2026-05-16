<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbuseReport;
use App\Models\AuditLog;
use App\Models\BotRule;
use App\Models\ClickLog;
use App\Models\ContactMessage;
use App\Models\Payment;
use App\Models\ShortLink;
use App\Models\Subscription;
use App\Models\User;
use App\Services\BetaModeService;
use Illuminate\Support\Carbon;

class AdminDashboardController extends Controller
{
    public function __invoke(BetaModeService $beta)
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();
        $suspendedUsers = User::where('status', 'suspended')->count();
        $totalLinks = ShortLink::count();
        $activeLinks = ShortLink::where('is_active', true)->count();
        $flaggedLinks = ShortLink::where('is_flagged', true)->count();
        $totalClicks = (int) ShortLink::sum('total_clicks');
        $humanClicks = (int) ShortLink::sum('human_clicks');
        $botClicks = (int) ShortLink::sum('bot_clicks');
        $botRateGlobal = $totalClicks > 0 ? round(($botClicks / $totalClicks) * 100, 1) : 0;
        $pendingPayments = Payment::where('status', 'pending')->count();
        $monthlyRevenue = (int) Payment::where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');
        $openAbuse = AbuseReport::where('status', 'open')->count();
        $unreadMessages = ContactMessage::where('status', 'unread')->count();
        $newUsersToday = User::whereDate('created_at', today())->count();
        $activeSubs = Subscription::where('status', 'active')->count();

        // 7-day click series
        $labels = []; $human = []; $bot = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::now()->subDays($i)->toDateString();
            $labels[] = Carbon::parse($d)->format('d/m');
            $human[] = ClickLog::whereDate('clicked_at', $d)->where('is_bot', false)->count();
            $bot[] = ClickLog::whereDate('clicked_at', $d)->where('is_bot', true)->count();
        }

        // 30-day new users
        $userLabels = []; $newUsers = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = Carbon::now()->subDays($i)->toDateString();
            $userLabels[] = Carbon::parse($d)->format('d/m');
            $newUsers[] = User::whereDate('created_at', $d)->count();
        }

        $recentUsers = User::latest()->take(8)->get();
        $recentLinks = ShortLink::with('user')->latest()->take(8)->get();
        $topLinks = ShortLink::with('user')->where('total_clicks', '>', 0)->orderByDesc('total_clicks')->take(8)->get();
        $highBotLinks = ShortLink::with('user')
            ->where('total_clicks', '>=', 10)
            ->orderByRaw('(bot_clicks * 1.0 / NULLIF(total_clicks,0)) DESC')
            ->take(6)->get();
        $recentPayments = Payment::with('user')->latest()->take(6)->get();
        $openAbuseList = AbuseReport::latest()->where('status', 'open')->take(5)->get();
        $latestMessages = ContactMessage::latest()->take(5)->get();
        $recentAudit = AuditLog::latest('created_at')->take(8)->get();

        return view('admin.dashboard', compact(
            'totalUsers','activeUsers','suspendedUsers','totalLinks','activeLinks','flaggedLinks',
            'totalClicks','humanClicks','botClicks','botRateGlobal','pendingPayments','monthlyRevenue',
            'openAbuse','unreadMessages','newUsersToday','activeSubs',
            'labels','human','bot','userLabels','newUsers',
            'recentUsers','recentLinks','topLinks','highBotLinks','recentPayments',
            'openAbuseList','latestMessages','recentAudit','beta'
        ));
    }
}
