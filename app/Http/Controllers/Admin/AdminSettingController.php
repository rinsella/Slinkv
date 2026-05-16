<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class AdminSettingController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            // General
            'site_name' => ['nullable', 'string', 'max:120'],
            'site_url' => ['nullable', 'url', 'max:255'],
            'registration_enabled' => ['nullable', 'in:0,1'],
            'default_plan' => ['nullable', 'string', 'max:60'],
            'free_plan_enabled' => ['nullable', 'in:0,1'],
            // SEO
            'site_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:300'],
            'favicon' => ['nullable', 'string', 'max:300'],
            // Support
            'support_email' => ['nullable', 'email', 'max:120'],
            'support_whatsapp' => ['nullable', 'string', 'max:30'],
            // Beta
            'beta_mode_enabled' => ['nullable', 'in:0,1'],
            'beta_free_all_features' => ['nullable', 'in:0,1'],
            'beta_banner_enabled' => ['nullable', 'in:0,1'],
            'beta_ends_at' => ['nullable', 'date'],
            'beta_announcement_text' => ['nullable', 'string', 'max:500'],
            // Billing
            'payment_gateway_mode' => ['nullable', Rule::in(['manual', 'midtrans', 'xendit'])],
            'manual_payment_instruction' => ['nullable', 'string', 'max:2000'],
            'invoice_expiration_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            // Security
            'block_private_urls' => ['nullable', 'in:0,1'],
            'enable_abuse_report' => ['nullable', 'in:0,1'],
            'default_bot_threshold' => ['nullable', 'integer', 'min:0', 'max:100'],
            'redirect_rate_limit' => ['nullable', 'integer', 'min:10', 'max:10000'],
            // Maintenance
            'maintenance_mode' => ['nullable', 'in:0,1'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
        ]);

        // Booleans: ensure each defined toggle is saved as 0 if missing.
        $booleanKeys = [
            'registration_enabled', 'free_plan_enabled',
            'beta_mode_enabled', 'beta_free_all_features', 'beta_banner_enabled',
            'block_private_urls', 'enable_abuse_report', 'maintenance_mode',
        ];
        foreach ($booleanKeys as $b) {
            if ($request->has("__toggle_{$b}")) {
                $data[$b] = $request->input($b) === '1' ? '1' : '0';
            }
        }

        $original = Setting::whereIn('key', array_keys($data))->pluck('value', 'key')->all();
        foreach ($data as $key => $value) {
            if ($value === null) continue;
            Setting::set($key, (string) $value);
        }
        Cache::flush();

        $this->audit->log('setting_update', null, $original, $data);

        return back()->with('success', 'Pengaturan disimpan.');
    }
}
