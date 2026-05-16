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
        $viteOk = file_exists($viteManifest);
        $favicon = public_path('favicon.ico');
        $faviconSize = file_exists($favicon) ? filesize($favicon) : 0;
        $appleIcon = public_path('apple-touch-icon.png');
        $manifest = public_path('site.webmanifest');
        $installerExists = file_exists(public_path('install.php'));
        $installerLocked = file_exists(storage_path('installed.lock'));
        $isProd = app()->environment('production');

        if (!$installerExists) {
            $installerStatus = ['removed/secure', true];
        } elseif ($installerLocked) {
            $installerStatus = ['present but locked (installed.lock exists)', true];
        } else {
            $installerStatus = ['WARNING — installer active without installed.lock', false];
        }

        $checks = [
            'PHP Version' => [PHP_VERSION, version_compare(PHP_VERSION, '8.2.0', '>=')],
            'Laravel' => [app()->version(), true],
            'APP_KEY' => [config('app.key') ? 'set' : 'missing', (bool) config('app.key')],
            'APP_DEBUG' => [config('app.debug') ? 'ON (matikan di production!)' : 'off', !config('app.debug')],
            'APP_URL' => [config('app.url'), filter_var(config('app.url'), FILTER_VALIDATE_URL) !== false],
            'Database' => $db,
            'Storage Writable' => [is_writable(storage_path()) ? 'yes' : 'no', is_writable(storage_path())],
            'Cache Writable' => [is_writable(storage_path('framework/cache')) ? 'yes' : 'no', is_writable(storage_path('framework/cache'))],
            'HTTPS' => [request()->isSecure() ? 'yes' : 'no (gunakan HTTPS di production)', request()->isSecure() || !$isProd],
            'Installer Status' => $installerStatus,
            'installed.lock' => [$installerLocked ? 'present' : ($isProd ? 'MISSING (production!)' : 'absent (dev)'), $installerLocked || !$isProd],
            'composer.lock' => [file_exists(base_path('composer.lock')) ? 'present' : 'missing', file_exists(base_path('composer.lock'))],
            'vendor/autoload.php' => [file_exists(base_path('vendor/autoload.php')) ? 'present' : 'missing', file_exists(base_path('vendor/autoload.php'))],
            'package.json' => [file_exists(base_path('package.json')) ? 'present' : 'missing', file_exists(base_path('package.json'))],
            'node_modules' => [is_dir(base_path('node_modules')) ? 'present' : 'absent (jalankan npm install)', is_dir(base_path('node_modules'))],
            'favicon.ico' => [file_exists($favicon) && $faviconSize > 0 ? "ok ({$faviconSize} bytes)" : 'missing/empty', file_exists($favicon) && $faviconSize > 0],
            'apple-touch-icon.png' => [file_exists($appleIcon) ? 'ok' : 'missing', file_exists($appleIcon)],
            'site.webmanifest' => [file_exists($manifest) ? 'ok' : 'missing', file_exists($manifest)],
            'Vite Manifest' => [$viteOk ? 'ok (build present)' : 'missing - jalankan npm run build', $viteOk],
            'QR Code (endroid/qr-code)' => [class_exists(\Endroid\QrCode\Builder\Builder::class) ? 'installed' : 'missing', class_exists(\Endroid\QrCode\Builder\Builder::class)],
            'GD/Imagick (untuk QR PNG)' => [extension_loaded('gd') ? 'gd' : (extension_loaded('imagick') ? 'imagick' : 'none — QR PNG fallback ke SVG'), extension_loaded('gd') || extension_loaded('imagick')],
        ];
        return view('admin.health', compact('checks'));
    }
}
