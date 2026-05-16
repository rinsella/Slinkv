<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class AdminHealthController extends Controller
{
    public function __invoke()
    {
        $checks = [
            'PHP Version' => [PHP_VERSION, version_compare(PHP_VERSION, '8.2.0', '>=')],
            'Laravel' => [app()->version(), true],
            'APP_KEY' => [config('app.key') ? 'set' : 'missing', (bool) config('app.key')],
            'APP_DEBUG' => [config('app.debug') ? 'on' : 'off', !config('app.debug')],
            'Database' => [(function () { try { DB::connection()->getPdo(); return 'connected'; } catch (\Throwable $e) { return $e->getMessage(); } })(), true],
            'Storage Writable' => [is_writable(storage_path()) ? 'yes' : 'no', is_writable(storage_path())],
            'Cache Writable' => [is_writable(storage_path('framework/cache')) ? 'yes' : 'no', is_writable(storage_path('framework/cache'))],
            'HTTPS' => [request()->isSecure() ? 'yes' : 'no', true],
            'Installer Locked' => [file_exists(storage_path('installed.lock')) ? 'yes' : 'no', file_exists(storage_path('installed.lock'))],
            'install.php removed' => [!file_exists(public_path('install.php')) ? 'yes' : 'no', !file_exists(public_path('install.php'))],
        ];
        return view('admin.health', compact('checks'));
    }
}
