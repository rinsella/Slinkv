<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // Mode FREE-FOREVER: hanya satu plan "Free" dengan semua fitur unlimited.
        // Plan berbayar dihapus selama tahap beta. Schema dipertahankan untuk
        // dapat ditambahkan kembali tanpa migrasi ulang.
        Plan::query()->where('slug', '!=', 'free')->delete();

        Plan::updateOrCreate(
            ['slug' => 'free'],
            [
                'name' => 'Free',
                'price' => 0,
                'billing_period' => 'free',
                'max_links' => null,
                'max_clicks_per_link' => null,
                'analytics_retention_days' => 365,
                'bot_protection_level' => 'advanced',
                'geo_filter_limit' => null,
                'has_fallback_url' => true,
                'has_custom_alias' => true,
                'has_qr_code' => true,
                'has_export_csv' => true,
                'has_audit_report' => true,
                'sort_order' => 1,
            ]
        );
    }
}
