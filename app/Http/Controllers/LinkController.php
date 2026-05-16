<?php

namespace App\Http\Controllers;

use App\Models\ClickLog;
use App\Models\ShortLink;
use App\Services\PlanLimitService;
use App\Services\ShortLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class LinkController extends Controller
{
    public function __construct(
        private ShortLinkService $links,
        private PlanLimitService $limits,
    ) {}

    public function index(Request $request)
    {
        $q = ShortLink::where('user_id', $request->user()->id);

        if ($s = trim($request->get('q', ''))) {
            $q->where(function ($w) use ($s) {
                $w->where('title', 'like', "%{$s}%")
                  ->orWhere('slug', 'like', "%{$s}%")
                  ->orWhere('destination_url', 'like', "%{$s}%");
            });
        }
        $status = $request->get('status');
        if ($status === 'active') $q->where('is_active', true);
        elseif ($status === 'inactive') $q->where('is_active', false);
        elseif ($status === 'expired') $q->whereNotNull('expires_at')->where('expires_at', '<', now());

        $sort = $request->get('sort', 'newest');
        match ($sort) {
            'clicks' => $q->orderByDesc('total_clicks'),
            'bot' => $q->where('total_clicks', '>', 0)->orderByRaw('(bot_clicks*1.0/total_clicks) DESC'),
            default => $q->latest(),
        };

        $links = $q->paginate(15)->withQueryString();
        $plan = $this->limits->planFor($request->user());
        $activeCount = ShortLink::where('user_id', $request->user()->id)->where('is_active', true)->count();

        return view('dashboard.links.index', compact('links','plan','activeCount'));
    }

    public function create(Request $request)
    {
        $plan = $this->limits->planFor($request->user());
        $prefill = $request->session()->get('prefill_url')
            ?? $request->session()->pull('pending_destination_url');
        return view('dashboard.links.create', compact('plan', 'prefill'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $plan = $this->limits->planFor($user);

        if (!$this->limits->canCreateLink($user)) {
            return back()->withInput()->withErrors([
                'limit' => "Limit paket {$plan->name} sudah tercapai (maks {$plan->max_links} link aktif). Silakan upgrade paket.",
            ]);
        }

        $rules = [
            'destination_url' => ['required', 'url', 'max:2048'],
            'title' => ['nullable', 'string', 'max:200'],
            'fallback_url' => ['nullable', 'url', 'max:2048'],
            'custom_alias' => ['nullable', 'string', 'min:3', 'max:32', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'bot_protection_enabled' => ['nullable', 'boolean'],
            'geo_filter_enabled' => ['nullable', 'boolean'],
            'allowed_countries' => ['nullable', 'string'],
            'blocked_countries' => ['nullable', 'string'],
            'device_filter' => ['nullable', 'in:all,desktop,mobile,tablet'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'password' => ['nullable', 'string', 'min:4', 'max:64'],
        ];
        $data = $request->validate($rules);

        if ($err = $this->links->validateDestination($data['destination_url'])) {
            return back()->withInput()->withErrors(['destination_url' => $err]);
        }

        if (!empty($data['fallback_url'])) {
            if (!$this->limits->canUseFallback($user)) {
                return back()->withInput()->withErrors(['fallback_url' => 'Fitur fallback URL tidak tersedia di paket Anda.']);
            }
            if ($err = $this->links->validateDestination($data['fallback_url'])) {
                return back()->withInput()->withErrors(['fallback_url' => $err]);
            }
        }

        $alias = $data['custom_alias'] ?? null;
        if ($alias) {
            if (!$this->limits->canUseCustomAlias($user)) {
                return back()->withInput()->withErrors(['custom_alias' => 'Custom alias tidak tersedia di paket Anda.']);
            }
            if ($this->links->isReserved($alias)) {
                return back()->withInput()->withErrors(['custom_alias' => 'Alias ini direservasi.']);
            }
            if (ShortLink::where('slug', $alias)->exists()) {
                return back()->withInput()->withErrors(['custom_alias' => 'Alias sudah digunakan.']);
            }
        }
        $slug = $alias ?: $this->links->generateUniqueSlug();

        $allowed = $this->parseCountries($data['allowed_countries'] ?? '');
        $blocked = $this->parseCountries($data['blocked_countries'] ?? '');

        if (!empty($data['geo_filter_enabled']) && $plan->geo_filter_limit !== null) {
            if (count($allowed) > (int) $plan->geo_filter_limit) {
                return back()->withInput()->withErrors(['allowed_countries' => "Maksimal {$plan->geo_filter_limit} negara untuk paket Anda."]);
            }
        }

        $link = ShortLink::create([
            'user_id' => $user->id,
            'title' => $data['title'] ?? null,
            'slug' => $slug,
            'destination_url' => $data['destination_url'],
            'fallback_url' => $data['fallback_url'] ?? null,
            'bot_protection_enabled' => (bool) ($data['bot_protection_enabled'] ?? true),
            'geo_filter_enabled' => (bool) ($data['geo_filter_enabled'] ?? false),
            'allowed_countries' => $allowed ?: null,
            'blocked_countries' => $blocked ?: null,
            'device_filter' => $data['device_filter'] ?? 'all',
            'expires_at' => $data['expires_at'] ?? null,
            'password' => !empty($data['password']) ? bcrypt($data['password']) : null,
            'is_active' => true,
        ]);

        return redirect()->route('dashboard.links.index')->with('success', 'Shortlink berhasil dibuat: ' . $link->shortUrl());
    }

    public function show(ShortLink $link)
    {
        Gate::authorize('view', $link);
        return redirect()->route('dashboard.links.analytics', $link);
    }

    public function edit(ShortLink $link)
    {
        Gate::authorize('update', $link);
        $plan = $this->limits->planFor(request()->user());
        return view('dashboard.links.edit', compact('link', 'plan'));
    }

    public function update(Request $request, ShortLink $link)
    {
        Gate::authorize('update', $link);

        $data = $request->validate([
            'destination_url' => ['required', 'url', 'max:2048'],
            'title' => ['nullable', 'string', 'max:200'],
            'fallback_url' => ['nullable', 'url', 'max:2048'],
            'bot_protection_enabled' => ['nullable', 'boolean'],
            'geo_filter_enabled' => ['nullable', 'boolean'],
            'allowed_countries' => ['nullable', 'string'],
            'blocked_countries' => ['nullable', 'string'],
            'device_filter' => ['nullable', 'in:all,desktop,mobile,tablet'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        if ($err = $this->links->validateDestination($data['destination_url'])) {
            return back()->withInput()->withErrors(['destination_url' => $err]);
        }
        if (!empty($data['fallback_url']) && ($err = $this->links->validateDestination($data['fallback_url']))) {
            return back()->withInput()->withErrors(['fallback_url' => $err]);
        }
        $link->update([
            'title' => $data['title'] ?? $link->title,
            'destination_url' => $data['destination_url'],
            'fallback_url' => $data['fallback_url'] ?? null,
            'bot_protection_enabled' => (bool) ($data['bot_protection_enabled'] ?? false),
            'geo_filter_enabled' => (bool) ($data['geo_filter_enabled'] ?? false),
            'allowed_countries' => $this->parseCountries($data['allowed_countries'] ?? '') ?: null,
            'blocked_countries' => $this->parseCountries($data['blocked_countries'] ?? '') ?: null,
            'device_filter' => $data['device_filter'] ?? 'all',
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? $link->is_active),
        ]);
        return redirect()->route('dashboard.links.index')->with('success', 'Link diperbarui.');
    }

    public function destroy(ShortLink $link)
    {
        Gate::authorize('delete', $link);
        $link->delete();
        return back()->with('success', 'Link dihapus.');
    }

    public function toggle(ShortLink $link)
    {
        Gate::authorize('update', $link);
        $link->update(['is_active' => !$link->is_active]);
        return back()->with('success', $link->is_active ? 'Link diaktifkan.' : 'Link dinonaktifkan.');
    }

    public function analytics(Request $request, ShortLink $link)
    {
        Gate::authorize('view', $link);
        $range = $request->get('range', '7');
        $days = (int) $range; $days = max(1, min($days, 90));
        $since = Carbon::now()->subDays($days - 1)->startOfDay();

        $clicks = ClickLog::where('short_link_id', $link->id)->where('clicked_at', '>=', $since);

        $total = (clone $clicks)->count();
        $human = (clone $clicks)->where('is_bot', false)->count();
        $bot = (clone $clicks)->where('is_bot', true)->count();
        $unique = (clone $clicks)->distinct('ip_hash')->count('ip_hash');
        $botRate = $total > 0 ? round(($bot / $total) * 100, 1) : 0;

        $labels = []; $humanSeries = []; $botSeries = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = Carbon::now()->subDays($i)->toDateString();
            $labels[] = Carbon::parse($d)->format('d/m');
            $humanSeries[] = (clone $clicks)->whereDate('clicked_at', $d)->where('is_bot', false)->count();
            $botSeries[] = (clone $clicks)->whereDate('clicked_at', $d)->where('is_bot', true)->count();
        }

        $topCountry = (clone $clicks)->selectRaw('country_name, count(*) as c')->whereNotNull('country_name')->groupBy('country_name')->orderByDesc('c')->take(5)->get();
        $topSource = (clone $clicks)->selectRaw('source_platform, count(*) as c')->whereNotNull('source_platform')->groupBy('source_platform')->orderByDesc('c')->take(5)->get();
        $topDevice = (clone $clicks)->selectRaw('device_type, count(*) as c')->whereNotNull('device_type')->groupBy('device_type')->orderByDesc('c')->take(5)->get();

        $recent = (clone $clicks)->latest('clicked_at')->take(20)->get();

        return view('dashboard.links.analytics', compact('link','total','human','bot','unique','botRate','labels','humanSeries','botSeries','topCountry','topSource','topDevice','recent','days'));
    }

    public function qr(ShortLink $link)
    {
        Gate::authorize('view', $link);
        $url = $link->shortUrl();
        // Simple QR via SVG using an external-free placeholder text-based fallback
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 220 260" width="220" height="260">'
            . '<rect width="100%" height="100%" fill="#fff"/>'
            . '<rect x="20" y="20" width="180" height="180" fill="none" stroke="#0F172A" stroke-width="6"/>'
            . '<text x="110" y="120" text-anchor="middle" font-family="monospace" font-size="14" fill="#0F172A">QR Placeholder</text>'
            . '<text x="110" y="240" text-anchor="middle" font-family="sans-serif" font-size="12" fill="#0F172A">' . htmlspecialchars($url, ENT_XML1) . '</text>'
            . '</svg>';
        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }

    private function parseCountries(?string $raw): array
    {
        if (!$raw) return [];
        return collect(explode(',', $raw))
            ->map(fn ($v) => strtoupper(trim($v)))
            ->filter(fn ($v) => preg_match('/^[A-Z]{2}$/', $v))
            ->unique()->values()->all();
    }
}
