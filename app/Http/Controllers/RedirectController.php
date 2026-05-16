<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use App\Services\RedirectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RedirectController extends Controller
{
    public function __invoke(Request $request, string $slug, RedirectService $service)
    {
        // Reserved path safety: should not happen because routes registered first, but double-guard
        if (in_array(strtolower($slug), \App\Services\ShortLinkService::RESERVED_SLUGS, true)) {
            abort(404);
        }

        $result = $service->handle($slug, $request);

        return match ($result['action']) {
            'redirect' => redirect()->away($result['url'], 302),
            'expired' => response()->view('redirect.expired', ['link' => $result['link']], 410),
            'inactive' => response()->view('redirect.inactive', ['link' => $result['link']], 410),
            'blocked' => response()->view('redirect.blocked', ['link' => $result['link'], 'reason' => $result['reason'] ?? 'security'], 403),
            'quota_exceeded' => response()->view('redirect.quota', ['link' => $result['link']], 429),
            'password_required' => response()->view('redirect.password', ['slug' => $result['slug']], 401),
            default => response()->view('errors.404', [], 404),
        };
    }

    public function unlock(Request $request, string $slug)
    {
        if (in_array(strtolower($slug), \App\Services\ShortLinkService::RESERVED_SLUGS, true)) {
            abort(404);
        }
        $request->validate(['password' => ['required', 'string', 'max:128']]);

        $link = ShortLink::where('slug', $slug)->first();
        if (!$link || empty($link->password)) {
            return redirect('/'.$slug);
        }

        if (!Hash::check($request->input('password'), $link->password)) {
            return back()->withErrors(['password' => 'Password salah. Silakan coba lagi.']);
        }

        $unlocked = (array) $request->session()->get('unlocked_links', []);
        $unlocked[$slug] = true;
        $request->session()->put('unlocked_links', $unlocked);

        return redirect('/'.$slug);
    }
}
