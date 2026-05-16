<?php

namespace App\Http\Controllers;

use App\Models\ClickLog;
use App\Models\ShortLink;
use App\Services\PlanLimitService;
use App\Services\ShortLinkService;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        if (!empty($data['geo_filter_enabled'])) {
            $geoLimit = $this->limits->geoFilterLimit($user);
            if ($geoLimit !== null && count($allowed) > $geoLimit) {
                return back()->withInput()->withErrors(['allowed_countries' => "Maksimal {$geoLimit} negara untuk paket Anda."]);
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
        $user = $request->user();

        $data = $request->validate([
            'destination_url' => ['required', 'url', 'max:2048'],
            'title' => ['nullable', 'string', 'max:200'],
            'fallback_url' => ['nullable', 'url', 'max:2048'],
            'custom_alias' => ['nullable', 'string', 'min:3', 'max:32', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'bot_protection_enabled' => ['nullable', 'boolean'],
            'geo_filter_enabled' => ['nullable', 'boolean'],
            'allowed_countries' => ['nullable', 'string'],
            'blocked_countries' => ['nullable', 'string'],
            'device_filter' => ['nullable', 'in:all,desktop,mobile,tablet'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:4', 'max:64'],
            'remove_password' => ['nullable', 'boolean'],
        ]);
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

        $allowed = $this->parseCountries($data['allowed_countries'] ?? '');
        $blocked = $this->parseCountries($data['blocked_countries'] ?? '');
        if (!empty($data['geo_filter_enabled'])) {
            $geoLimit = $this->limits->geoFilterLimit($user);
            if ($geoLimit !== null && count($allowed) > $geoLimit) {
                return back()->withInput()->withErrors(['allowed_countries' => "Maksimal {$geoLimit} negara untuk paket Anda."]);
            }
        }

        $update = [
            'title' => $data['title'] ?? $link->title,
            'destination_url' => $data['destination_url'],
            'fallback_url' => $data['fallback_url'] ?? null,
            'bot_protection_enabled' => (bool) ($data['bot_protection_enabled'] ?? false),
            'geo_filter_enabled' => (bool) ($data['geo_filter_enabled'] ?? false),
            'allowed_countries' => $allowed ?: null,
            'blocked_countries' => $blocked ?: null,
            'device_filter' => $data['device_filter'] ?? 'all',
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? $link->is_active),
        ];

        // Custom alias change (gated + reserved + unique)
        $alias = $data['custom_alias'] ?? null;
        if ($alias && $alias !== $link->slug) {
            if (!$this->limits->canUseCustomAlias($user)) {
                return back()->withInput()->withErrors(['custom_alias' => 'Custom alias tidak tersedia di paket Anda.']);
            }
            if ($this->links->isReserved($alias)) {
                return back()->withInput()->withErrors(['custom_alias' => 'Alias ini direservasi.']);
            }
            if (ShortLink::where('slug', $alias)->where('id', '!=', $link->id)->exists()) {
                return back()->withInput()->withErrors(['custom_alias' => 'Alias sudah digunakan.']);
            }
            $update['slug'] = $alias;
        }

        // Password handling
        if ($request->boolean('remove_password')) {
            $update['password'] = null;
        } elseif (!empty($data['password'])) {
            $update['password'] = bcrypt($data['password']);
        }

        $link->update($update);
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

    public function qr(Request $request, ShortLink $link)
    {
        Gate::authorize('view', $link);
        if (!$this->limits->canUseQrCode($request->user())) {
            abort(403, 'QR Code tidak tersedia di paket Anda.');
        }
        $result = Builder::create()
            ->writer(new SvgWriter())
            ->data($link->shortUrl())
            ->size(300)
            ->margin(10)
            ->build();

        $headers = ['Content-Type' => $result->getMimeType()];
        if ($request->boolean('download')) {
            $headers['Content-Disposition'] = 'attachment; filename="slinkv-' . $link->slug . '.svg"';
        }
        return response($result->getString(), 200, $headers);
    }

    public function qrPng(Request $request, ShortLink $link)
    {
        Gate::authorize('view', $link);
        if (!$this->limits->canUseQrCode($request->user())) {
            abort(403, 'QR Code tidak tersedia di paket Anda.');
        }
        if (!extension_loaded('gd') && !extension_loaded('imagick')) {
            return $this->qr($request, $link);
        }
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($link->shortUrl())
            ->size(300)
            ->margin(10)
            ->build();
        $headers = ['Content-Type' => $result->getMimeType()];
        if ($request->boolean('download')) {
            $headers['Content-Disposition'] = 'attachment; filename="slinkv-' . $link->slug . '.png"';
        }
        return response($result->getString(), 200, $headers);
    }

    public function exportCsv(Request $request, ShortLink $link)
    {
        Gate::authorize('view', $link);
        if (!$this->limits->canExportCsv($request->user())) {
            abort(403, 'Export CSV tidak tersedia di paket Anda.');
        }

        $filename = 'slinkv-' . $link->slug . '-' . now()->format('Ymd-His') . '.csv';
        $columns = ['clicked_at','action','source_platform','country_code','country_name','city','device_type','browser','os','is_bot','bot_score','bot_reasons','referer','redirected_to'];

        return new StreamedResponse(function () use ($link, $columns) {
            $h = fopen('php://output', 'w');
            // BOM untuk Excel
            fwrite($h, "\xEF\xBB\xBF");
            fputcsv($h, $columns);
            ClickLog::where('short_link_id', $link->id)
                ->orderBy('clicked_at')
                ->chunk(1000, function ($rows) use ($h, $columns) {
                    foreach ($rows as $row) {
                        $line = [];
                        foreach ($columns as $c) {
                            $v = $row->{$c};
                            if (is_array($v)) $v = implode('|', $v);
                            if ($v instanceof \DateTimeInterface) $v = $v->format('Y-m-d H:i:s');
                            if (is_bool($v)) $v = $v ? '1' : '0';
                            $line[] = $v;
                        }
                        fputcsv($h, $line);
                    }
                });
            fclose($h);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function auditReport(Request $request, ShortLink $link)
    {
        Gate::authorize('view', $link);
        if (!$this->limits->canUseAuditReport($request->user())) {
            abort(403, 'Audit Report tidak tersedia di paket Anda.');
        }

        $days = (int) $request->get('range', 30);
        $days = max(1, min($days, 90));
        $since = Carbon::now()->subDays($days - 1)->startOfDay();
        $base = ClickLog::where('short_link_id', $link->id)->where('clicked_at', '>=', $since);

        $total = (clone $base)->count();
        $human = (clone $base)->where('is_bot', false)->count();
        $bot = (clone $base)->where('is_bot', true)->count();
        $blocked = (clone $base)->where('action', 'blocked')->count();
        $expired = (clone $base)->where('action', 'expired')->count();
        $quotaExceeded = (clone $base)->where('action', 'quota_exceeded')->count();
        $passwordRequired = (clone $base)->where('action', 'password_required')->count();
        $unique = (clone $base)->distinct('ip_hash')->count('ip_hash');
        $botRate = $total > 0 ? round(($bot / $total) * 100, 1) : 0;

        $topReasons = (clone $base)
            ->whereNotNull('bot_reasons')
            ->where('is_bot', true)
            ->get(['bot_reasons'])
            ->flatMap(fn ($r) => (array) $r->bot_reasons)
            ->countBy()
            ->sortDesc()
            ->take(10);

        $topCountry = (clone $base)->selectRaw('country_name, count(*) as c')->whereNotNull('country_name')->groupBy('country_name')->orderByDesc('c')->take(10)->get();
        $topSource = (clone $base)->selectRaw('source_platform, count(*) as c')->whereNotNull('source_platform')->groupBy('source_platform')->orderByDesc('c')->take(10)->get();
        $recentSuspicious = (clone $base)->where('is_bot', true)->latest('clicked_at')->take(25)->get();

        return view('dashboard.links.audit-report', compact(
            'link','days','total','human','bot','blocked','expired','quotaExceeded',
            'passwordRequired','unique','botRate','topReasons','topCountry','topSource','recentSuspicious'
        ));
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
