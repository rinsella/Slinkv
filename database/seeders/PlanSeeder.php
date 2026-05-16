<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'free', 'name' => 'Free', 'price' => 0, 'currency' => 'IDR',
                'billing_period' => 'free',
                'max_links' => 5, 'max_clicks_per_link' => 1000,
                'analytics_retention_days' => 7, 'bot_protection_level' => 'basic',
                'geo_filter_limit' => 0,
                'has_fallback_url' => false, 'has_custom_alias' => false,
                'has_qr_code' => true, 'has_export_csv' => false, 'has_audit_report' => false,
                'is_active' => true, 'sort_order' => 1,
            ],
            [
                'slug' => 'starter', 'name' => 'Starter', 'price' => 29000, 'currency' => 'IDR',
                'billing_period' => 'monthly',
                'max_links' => 20, 'max_clicks_per_link' => null,
                'analytics_retention_days' => 30, 'bot_protection_level' => 'advanced',
                'geo_filter_limit' => 3,
                'has_fallback_url' => true, 'has_custom_alias' => false,
                'has_qr_code' => true, 'has_export_csv' => false, 'has_audit_report' => false,
                'is_active' => true, 'sort_order' => 2,
            ],
            [
                'slug' => 'pro', 'name' => 'Pro', 'price' => 59000, 'currency' => 'IDR',
                'billing_period' => 'monthly',
                'max_links' => 100, 'max_clicks_per_link' => null,
                'analytics_retention_days' => 90, 'bot_protection_level' => 'advanced',
                'geo_filter_limit' => null,
                'has_fallback_url' => true, 'has_custom_alias' => true,
                'has_qr_code' => true, 'has_export_csv' => true, 'has_audit_report' => false,
                'is_active' => true, 'sort_order' => 3,
            ],
            [
                'slug' => 'business', 'name' => 'Business', 'price' => 999000, 'currency' => 'IDR',
                'billing_period' => 'yearly',
                'max_links' => null, 'max_clicks_per_link' => null,
                'analytics_retention_days' => 365, 'bot_protection_level' => 'advanced',
                'geo_filter_limit' => null,
                'has_fallback_url' => true, 'has_custom_alias' => true,
                'has_qr_code' => true, 'has_export_csv' => true, 'has_audit_report' => true,
                'is_active' => true, 'sort_order' => 4,
            ],
        ];

        foreach ($plans as $p) {
            Plan::updateOrCreate(['slug' => $p['slug']], $p);
        }
    }
}
