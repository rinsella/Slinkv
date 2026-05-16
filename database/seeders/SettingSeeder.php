<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // General
            ['site_name', 'SlinkV', 'general'],
            ['site_url', 'https://slinkv.net', 'general'],
            ['registration_enabled', '1', 'general'],
            ['default_plan', 'free', 'general'],
            ['free_plan_enabled', '1', 'general'],

            // SEO
            ['site_title', 'SlinkV - URL Shortener dengan Bot Protection & Analytics Real-time', 'seo'],
            ['meta_description', 'SlinkV adalah URL shortener profesional dengan analytics real-time, bot protection, geo filter, device tracking, dan traffic cleaner untuk iklan, affiliate, publisher, dan digital campaign.', 'seo'],
            ['og_image', '/og-image.svg', 'seo'],
            ['favicon', '/favicon.svg', 'seo'],

            // Support
            ['support_email', 'support@slinkv.net', 'support'],
            ['support_whatsapp', '6281234567890', 'support'],

            // Beta Mode
            ['beta_mode_enabled', '1', 'beta'],
            ['beta_free_all_features', '1', 'beta'],
            ['beta_banner_enabled', '1', 'beta'],
            ['beta_ends_at', '', 'beta'],
            ['beta_announcement_text', 'Selama masa beta, semua fitur SlinkV tersedia 100% gratis untuk semua pengguna.', 'beta'],

            // Billing
            ['payment_gateway_mode', 'manual', 'billing'],
            ['manual_payment_instruction', 'Transfer ke rekening BCA 1234567890 a.n. SlinkV. Setelah transfer, kirim bukti ke admin via WhatsApp.', 'billing'],
            ['invoice_expiration_hours', '24', 'billing'],

            // Security
            ['block_private_urls', '1', 'security'],
            ['enable_abuse_report', '1', 'security'],
            ['default_bot_threshold', '50', 'security'],
            ['redirect_rate_limit', '120', 'security'],

            // Analytics
            ['analytics_retention_default', '7', 'analytics'],

            // Maintenance
            ['maintenance_mode', '0', 'maintenance'],
            ['maintenance_message', 'Sedang dalam perawatan singkat. Coba lagi dalam beberapa menit.', 'maintenance'],
        ];

        foreach ($defaults as [$key, $value, $group]) {
            Setting::updateOrCreate(['key' => $key], [
                'value' => $value, 'type' => 'text', 'group' => $group,
            ]);
        }
    }
}
