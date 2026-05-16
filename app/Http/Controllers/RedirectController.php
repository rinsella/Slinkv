<?php

namespace App\Http\Controllers;

use App\Services\RedirectService;
use Illuminate\Http\Request;

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
            default => response()->view('errors.404', [], 404),
        };
    }
}
