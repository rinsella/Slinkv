<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceModeCheck
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Setting::get('maintenance_mode', '0') === '1' && !$request->is('admin*')) {
            $user = $request->user();
            if (!$user || !$user->isAdmin()) {
                return response()->view('errors.503', [], 503);
            }
        }
        return $next($request);
    }
}
