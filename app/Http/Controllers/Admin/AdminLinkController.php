<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClickLog;
use App\Models\ShortLink;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminLinkController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index(Request $request)
    {
        $q = ShortLink::with('user');
        if ($s = $request->get('q')) {
            $q->where(fn ($w) => $w->where('slug', 'like', "%{$s}%")
                ->orWhere('destination_url', 'like', "%{$s}%")
                ->orWhere('title', 'like', "%{$s}%"));
        }
        if ($email = $request->get('user')) {
            $q->whereHas('user', fn ($u) => $u->where('email', 'like', "%{$email}%"));
        }
        if ($request->filled('active')) {
            $q->where('is_active', $request->get('active') === '1');
        }
        if ($request->boolean('flagged')) {
            $q->where('is_flagged', true);
        }
        if ($request->boolean('high_bot')) {
            $q->where('total_clicks', '>=', 10)
              ->whereRaw('(bot_clicks * 1.0 / NULLIF(total_clicks,0)) >= 0.5');
        }
        $links = $q->latest()->paginate(25)->withQueryString();
        return view('admin.links.index', compact('links'));
    }

    public function show(ShortLink $link)
    {
        $link->load('user');
        $recentLogs = ClickLog::where('short_link_id', $link->id)->latest('clicked_at')->take(30)->get();
        $topCountries = ClickLog::where('short_link_id', $link->id)
            ->selectRaw('country_code, COUNT(*) as c')
            ->groupBy('country_code')->orderByDesc('c')->take(5)->get();
        $topSources = ClickLog::where('short_link_id', $link->id)
            ->selectRaw('source_platform, COUNT(*) as c')
            ->groupBy('source_platform')->orderByDesc('c')->take(5)->get();
        return view('admin.links.show', compact('link', 'recentLogs', 'topCountries', 'topSources'));
    }

    public function edit(ShortLink $link)
    {
        return view('admin.links.edit', compact('link'));
    }

    public function update(Request $request, ShortLink $link)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('short_links', 'slug')->ignore($link->id)],
            'destination_url' => ['required', 'url', 'max:2048'],
            'fallback_url' => ['nullable', 'url', 'max:2048'],
            'bot_protection_enabled' => ['nullable', 'boolean'],
            'geo_filter_enabled' => ['nullable', 'boolean'],
            'device_filter' => ['required', Rule::in(['all', 'desktop', 'mobile', 'tablet'])],
            'is_active' => ['nullable', 'boolean'],
            'is_flagged' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date'],
        ]);
        foreach (['bot_protection_enabled', 'geo_filter_enabled', 'is_active', 'is_flagged'] as $k) {
            $data[$k] = (bool) ($data[$k] ?? false);
        }
        $old = $link->only(array_keys($data));
        $link->update($data);
        [$o, $n] = $this->audit->diff($old, $link->only(array_keys($data)));
        $this->audit->log('link_update', $link, $o, $n);
        return redirect()->route('admin.links.show', $link)->with('success', 'Link diperbarui.');
    }

    public function toggle(ShortLink $link)
    {
        $link->update(['is_active' => !$link->is_active]);
        $this->audit->log('link_toggle', $link, null, ['is_active' => $link->is_active]);
        return back()->with('success', 'Status link diperbarui.');
    }

    public function flag(ShortLink $link)
    {
        $link->update(['is_flagged' => true]);
        $this->audit->log('link_flag', $link);
        return back()->with('success', 'Link ditandai mencurigakan.');
    }

    public function unflag(ShortLink $link)
    {
        $link->update(['is_flagged' => false]);
        $this->audit->log('link_unflag', $link);
        return back()->with('success', 'Flag dihapus.');
    }

    public function destroy(ShortLink $link)
    {
        $this->audit->log('link_delete', $link, $link->only(['slug', 'destination_url', 'user_id']));
        $link->delete();
        return redirect()->route('admin.links.index')->with('success', 'Link dihapus.');
    }
}
