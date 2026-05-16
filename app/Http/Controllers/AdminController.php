<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ClickLog;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\ShortLink;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();
        $totalLinks = ShortLink::count();
        $activeLinks = ShortLink::where('is_active', true)->count();
        $totalClicks = (int) ShortLink::sum('total_clicks');
        $humanClicks = (int) ShortLink::sum('human_clicks');
        $botClicks = (int) ShortLink::sum('bot_clicks');
        $newUsersToday = User::whereDate('created_at', today())->count();
        $monthlyRevenue = (int) Payment::where('status', 'paid')->whereMonth('paid_at', now()->month)->whereYear('paid_at', now()->year)->sum('amount');
        $botRateGlobal = $totalClicks > 0 ? round(($botClicks / $totalClicks) * 100, 1) : 0;

        $labels = []; $clickSeries = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::now()->subDays($i)->toDateString();
            $labels[] = Carbon::parse($d)->format('d/m');
            $clickSeries[] = ClickLog::whereDate('clicked_at', $d)->count();
        }

        $recentUsers = User::latest()->take(8)->get();
        $recentLinks = ShortLink::with('user')->latest()->take(8)->get();
        $topLinks = ShortLink::with('user')->orderByDesc('total_clicks')->take(8)->get();
        $recentPayments = Payment::with('user')->latest()->take(8)->get();

        return view('admin.dashboard', compact(
            'totalUsers','activeUsers','totalLinks','activeLinks','totalClicks',
            'humanClicks','botClicks','newUsersToday','monthlyRevenue','botRateGlobal',
            'labels','clickSeries','recentUsers','recentLinks','topLinks','recentPayments'
        ));
    }

    public function users(Request $request)
    {
        $q = User::query();
        if ($s = $request->get('q')) $q->where(fn($w) => $w->where('name','like',"%{$s}%")->orWhere('email','like',"%{$s}%"));
        $users = $q->latest()->paginate(20)->withQueryString();
        return view('admin.users.index', compact('users'));
    }

    public function userShow(User $user)
    {
        $user->loadCount('shortLinks');
        $payments = $user->payments()->latest()->take(10)->get();
        return view('admin.users.show', compact('user','payments'));
    }

    public function userSuspend(User $user)
    {
        $user->update(['status' => $user->status === 'active' ? 'suspended' : 'active']);
        return back()->with('success', 'Status user diperbarui.');
    }

    public function userPlan(Request $request, User $user)
    {
        $data = $request->validate(['plan_id' => ['required','exists:plans,id']]);
        $user->update(['plan_id' => $data['plan_id']]);
        return back()->with('success', 'Paket user diperbarui.');
    }

    public function links(Request $request)
    {
        $q = ShortLink::with('user');
        if ($s = $request->get('q')) $q->where(fn($w) => $w->where('slug','like',"%{$s}%")->orWhere('destination_url','like',"%{$s}%"));
        $links = $q->latest()->paginate(25)->withQueryString();
        return view('admin.links.index', compact('links'));
    }

    public function linkToggle(ShortLink $link)
    {
        $link->update(['is_active' => !$link->is_active]);
        return back()->with('success', 'Status link diperbarui.');
    }

    public function linkDestroy(ShortLink $link)
    {
        $link->delete();
        return back()->with('success', 'Link dihapus.');
    }

    public function clickLogs(Request $request)
    {
        $q = ClickLog::with('shortLink:id,slug,user_id');
        if ($t = $request->get('type')) {
            if ($t === 'bot') $q->where('is_bot', true);
            if ($t === 'human') $q->where('is_bot', false);
        }
        $logs = $q->latest('clicked_at')->paginate(30)->withQueryString();
        return view('admin.click-logs', compact('logs'));
    }

    public function botLogs(Request $request)
    {
        $logs = ClickLog::with('shortLink:id,slug')->where('is_bot', true)->latest('clicked_at')->paginate(30);
        return view('admin.bot-logs', compact('logs'));
    }

    public function plans()
    {
        $plans = Plan::orderBy('sort_order')->get();
        return view('admin.plans.index', compact('plans'));
    }

    public function subscriptions()
    {
        $subs = Subscription::with(['user','plan'])->latest()->paginate(30);
        return view('admin.subscriptions', compact('subs'));
    }

    public function payments(Request $request)
    {
        $q = Payment::with(['user','plan']);
        if ($s = $request->get('status')) $q->where('status', $s);
        $payments = $q->latest()->paginate(30)->withQueryString();
        return view('admin.payments', compact('payments'));
    }

    public function paymentMarkPaid(Payment $payment)
    {
        $payment->update(['status' => 'paid', 'paid_at' => now()]);
        if ($payment->subscription_id) {
            Subscription::where('id', $payment->subscription_id)->update([
                'status' => 'active',
                'started_at' => now(),
                'expires_at' => $payment->plan->billing_period === 'yearly' ? now()->addYear() : now()->addMonth(),
            ]);
            User::where('id', $payment->user_id)->update(['plan_id' => $payment->plan_id]);
        }
        return back()->with('success', 'Pembayaran ditandai paid.');
    }

    public function articles()
    {
        $articles = Article::latest()->paginate(20);
        return view('admin.articles.index', compact('articles'));
    }

    public function faqs()
    {
        $faqs = Faq::orderBy('sort_order')->get();
        return view('admin.faqs.index', compact('faqs'));
    }

    public function contactMessages()
    {
        $messages = ContactMessage::latest()->paginate(30);
        return view('admin.contact-messages', compact('messages'));
    }

    public function settings()
    {
        $settings = Setting::all()->keyBy('key');
        return view('admin.settings', compact('settings'));
    }

    public function settingsUpdate(Request $request)
    {
        foreach ($request->except('_token') as $key => $val) {
            Setting::set($key, (string) $val);
        }
        return back()->with('success', 'Pengaturan disimpan.');
    }

    public function healthCheck()
    {
        $checks = [
            'PHP Version' => [PHP_VERSION, version_compare(PHP_VERSION, '8.2.0', '>=')],
            'Laravel' => [app()->version(), true],
            'APP_KEY' => [config('app.key') ? 'set' : 'missing', (bool) config('app.key')],
            'APP_DEBUG' => [config('app.debug') ? 'on' : 'off', !config('app.debug')],
            'Database' => [(function(){ try { \DB::connection()->getPdo(); return 'connected'; } catch(\Throwable $e){ return $e->getMessage(); }})(), true],
            'Storage Writable' => [is_writable(storage_path()) ? 'yes' : 'no', is_writable(storage_path())],
            'Cache Writable' => [is_writable(storage_path('framework/cache')) ? 'yes' : 'no', is_writable(storage_path('framework/cache'))],
            'HTTPS' => [request()->isSecure() ? 'yes' : 'no', true],
            'Installer Locked' => [file_exists(storage_path('installed.lock')) ? 'yes' : 'no', file_exists(storage_path('installed.lock'))],
        ];
        return view('admin.health', compact('checks'));
    }
}
