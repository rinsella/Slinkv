<?php

namespace App\Http\Controllers;

use App\Models\ClickLog;
use App\Models\ShortLink;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $links = ShortLink::where('user_id', $user->id);
        $linkIds = (clone $links)->pluck('id');

        $totalLinks = (clone $links)->count();
        $activeLinks = (clone $links)->where('is_active', true)->count();
        $totalClicks = (int) (clone $links)->sum('total_clicks');
        $humanClicks = (int) (clone $links)->sum('human_clicks');
        $botClicks = (int) (clone $links)->sum('bot_clicks');
        $botRate = $totalClicks > 0 ? round(($botClicks / $totalClicks) * 100, 1) : 0;

        $clicks7Days = [];
        $labels7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::now()->subDays($i)->toDateString();
            $labels7Days[] = Carbon::parse($d)->format('d/m');
            $count = $linkIds->isEmpty() ? 0 : ClickLog::whereIn('short_link_id', $linkIds)
                ->whereDate('clicked_at', $d)->count();
            $clicks7Days[] = $count;
        }

        $hourlyHuman = []; $hourlyBot = []; $hourlyLabels = [];
        for ($i = 23; $i >= 0; $i--) {
            $start = Carbon::now()->subHours($i)->startOfHour();
            $end = (clone $start)->endOfHour();
            $hourlyLabels[] = $start->format('H:i');
            if ($linkIds->isEmpty()) {
                $hourlyHuman[] = 0; $hourlyBot[] = 0; continue;
            }
            $h = ClickLog::whereIn('short_link_id', $linkIds)->whereBetween('clicked_at', [$start, $end])->where('is_bot', false)->count();
            $b = ClickLog::whereIn('short_link_id', $linkIds)->whereBetween('clicked_at', [$start, $end])->where('is_bot', true)->count();
            $hourlyHuman[] = $h; $hourlyBot[] = $b;
        }

        $topSource = $linkIds->isEmpty() ? null : ClickLog::whereIn('short_link_id', $linkIds)
            ->selectRaw('source_platform, count(*) as c')->groupBy('source_platform')->orderByDesc('c')->value('source_platform');
        $topCountry = $linkIds->isEmpty() ? null : ClickLog::whereIn('short_link_id', $linkIds)
            ->selectRaw('country_name, count(*) as c')->whereNotNull('country_name')->groupBy('country_name')->orderByDesc('c')->value('country_name');

        $recent = (clone $links)->latest()->take(8)->get();

        return view('dashboard.index', compact(
            'totalLinks','activeLinks','totalClicks','humanClicks','botClicks','botRate',
            'clicks7Days','labels7Days','hourlyHuman','hourlyBot','hourlyLabels',
            'topSource','topCountry','recent'
        ));
    }

    public function statistics(Request $request)
    {
        $user = $request->user();
        $linkIds = ShortLink::where('user_id', $user->id)->pluck('id');

        $days = []; $labels = []; $human = []; $bot = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = Carbon::now()->subDays($i)->toDateString();
            $labels[] = Carbon::parse($d)->format('d/m');
            $h = $linkIds->isEmpty() ? 0 : ClickLog::whereIn('short_link_id', $linkIds)->whereDate('clicked_at', $d)->where('is_bot', false)->count();
            $b = $linkIds->isEmpty() ? 0 : ClickLog::whereIn('short_link_id', $linkIds)->whereDate('clicked_at', $d)->where('is_bot', true)->count();
            $human[] = $h; $bot[] = $b;
        }

        $topLinks = ShortLink::where('user_id', $user->id)->orderByDesc('total_clicks')->take(10)->get();
        $botHeavy = ShortLink::where('user_id', $user->id)->where('total_clicks', '>', 0)
            ->orderByRaw('(bot_clicks * 1.0 / total_clicks) DESC')->take(10)->get();

        return view('dashboard.statistics', compact('labels','human','bot','topLinks','botHeavy'));
    }

    public function locationDevice(Request $request)
    {
        $user = $request->user();
        $linkIds = ShortLink::where('user_id', $user->id)->pluck('id');
        $since = Carbon::now()->subDays(7);

        $base = fn () => $linkIds->isEmpty() ? collect() : ClickLog::whereIn('short_link_id', $linkIds)
            ->where('clicked_at', '>=', $since)->where('is_bot', false);

        $countries = $linkIds->isEmpty() ? collect() : $base()->selectRaw('country_name, count(*) as c')
            ->whereNotNull('country_name')->groupBy('country_name')->orderByDesc('c')->take(10)->get();
        $devices = $linkIds->isEmpty() ? collect() : $base()->selectRaw('device_type, count(*) as c')
            ->whereNotNull('device_type')->groupBy('device_type')->orderByDesc('c')->get();
        $browsers = $linkIds->isEmpty() ? collect() : $base()->selectRaw('browser, count(*) as c')
            ->whereNotNull('browser')->groupBy('browser')->orderByDesc('c')->take(10)->get();
        $oses = $linkIds->isEmpty() ? collect() : $base()->selectRaw('os, count(*) as c')
            ->whereNotNull('os')->groupBy('os')->orderByDesc('c')->take(10)->get();
        $cities = $linkIds->isEmpty() ? collect() : $base()->selectRaw('city, count(*) as c')
            ->whereNotNull('city')->groupBy('city')->orderByDesc('c')->take(10)->get();

        return view('dashboard.location-device', compact('countries','devices','browsers','oses','cities'));
    }

    public function sources(Request $request)
    {
        $user = $request->user();
        $linkIds = ShortLink::where('user_id', $user->id)->pluck('id');

        $sources = $linkIds->isEmpty() ? collect() : ClickLog::whereIn('short_link_id', $linkIds)
            ->selectRaw('source_platform, count(*) as total, sum(case when is_bot=0 then 1 else 0 end) as human, sum(case when is_bot=1 then 1 else 0 end) as bot')
            ->groupBy('source_platform')->orderByDesc('total')->get();

        return view('dashboard.sources', compact('sources'));
    }

    public function referral(Request $request)
    {
        $user = $request->user();
        $referrals = \App\Models\User::where('referred_by', $user->id)->take(20)->get();
        return view('dashboard.referral', compact('referrals'));
    }

    public function billing(Request $request)
    {
        $user = $request->user();
        $plan = $user->effectivePlan();
        $sub = $user->activeSubscription();
        $invoices = \App\Models\Payment::where('user_id', $user->id)->latest()->take(20)->get();
        return view('dashboard.billing', compact('plan','sub','invoices'));
    }

    public function settings(Request $request)
    {
        return view('dashboard.settings');
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email,' . $user->id],
        ]);
        $user->update($data);
        return back()->with('success', 'Profil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'current_password' => ['required','current_password'],
            'password' => ['required','confirmed','min:8'],
        ]);
        $user->update(['password' => $data['password']]);
        return back()->with('success', 'Password diperbarui.');
    }
}
