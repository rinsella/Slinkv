<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['site_name', 'SlinkV', 'general'],
            ['site_title', 'SlinkV - URL Shortener dengan Bot Protection & Analytics Real-time', 'seo'],
            ['meta_description', 'SlinkV adalah URL shortener profesional dengan analytics real-time, bot protection, geo filter, device tracking, dan traffic cleaner untuk iklan, affiliate, publisher, dan digital campaign.', 'seo'],
            ['site_url', 'https://slinkv.net', 'general'],
            ['support_email', 'support@slinkv.net', 'support'],
            ['support_whatsapp', '6281234567890', 'support'],
            ['registration_enabled', '1', 'general'],
            ['free_plan_enabled', '1', 'general'],
            ['default_plan', 'free', 'general'],
            ['maintenance_mode', '0', 'general'],
            ['payment_gateway_mode', 'manual', 'billing'],
            ['analytics_retention_default', '7', 'analytics'],
        ];

        foreach ($defaults as [$key, $value, $group]) {
            Setting::updateOrCreate(['key' => $key], [
                'value' => $value, 'type' => 'text', 'group' => $group,
            ]);
        }
    }
}
