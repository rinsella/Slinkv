<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class AdminHealthController extends Controller
{
    public function __invoke()
    {
        // DB check
        try {
            DB::connection()->getPdo();
            $db = ['connected', true];
        } catch (\Throwable $e) {
            $db = ['failed: ' . $e->getMessage(), false];
        }

        $viteManifest = public_path('build/manifest.json');
        $favicon = public_path('favicon.ico');
        $faviconSize = file_exists($favicon) ? filesize($favicon) : 0;
        $appleIcon = public_path('apple-touch-icon.png');
        $manifest = public_path('site.webmanifest');

        $checks = [
            'PHP Version' => [PHP_VERSION, version_compare(PHP_VERSION, '8.2.0', '>=')],
            'Laravel' => [app()->version(), true],
            'APP_KEY' => [config('app.key') ? 'set' : 'missing', (bool) config('app.key')],
            'APP_DEBUG' => [config('app.debug') ? 'ON (matikan di production!)' : 'off', !config('app.debug')],
            'APP_URL' => [config('app.url'), filter_var(config('app.url'), FILTER_VALIDATE_URL) !== false],
            'Database' => $db,
            'Storage Writable' => [is_writable(storage_path()) ? 'yes' : 'no', is_writable(storage_path())],
            'Cache Writable' => [is_writable(storage_path('framework/cache')) ? 'yes' : 'no', is_writable(storage_path('framework/cache'))],
            'HTTPS' => [request()->isSecure() ? 'yes' : 'no (gunakan HTTPS di production)', request()->isSecure()],
            'Installer Locked' => [file_exists(storage_path('installed.lock')) ? 'yes' : 'no', file_exists(storage_path('installed.lock'))],
            'install.php removed' => [!file_exists(public_path('install.php')) ? 'yes' : 'WARNING — masih ada, hapus setelah install di production', !file_exists(public_path('install.php'))],
            'favicon.ico' => [file_exists($favicon) && $faviconSize > 0 ? "ok ({$faviconSize} bytes)" : 'missing/empty', file_exists($favicon) && $faviconSize > 0],
            'apple-touch-icon.png' => [file_exists($appleIcon) ? 'ok' : 'missing', file_exists($appleIcon)],
            'site.webmanifest' => [file_exists($manifest) ? 'ok' : 'missing', file_exists($manifest)],
            'Vite Manifest' => [file_exists($viteManifest) ? 'ok (build present)' : 'tidak ada — masih pakai CDN (Tailwind/Alpine/Chart.js)', true],
            'QR Code (endroid/qr-code)' => [class_exists(\Endroid\QrCode\Builder\Builder::class) ? 'installed' : 'missing', class_exists(\Endroid\QrCode\Builder\Builder::class)],
            'GD/Imagick (untuk QR PNG)' => [extension_loaded('gd') ? 'gd' : (extension_loaded('imagick') ? 'imagick' : 'none — QR PNG fallback ke SVG'), extension_loaded('gd') || extension_loaded('imagick')],
        ];
        return view('admin.health', compact('checks'));
    }
}
