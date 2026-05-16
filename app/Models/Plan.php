<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'price', 'currency', 'billing_period',
        'max_links', 'max_clicks_per_link', 'analytics_retention_days',
        'bot_protection_level', 'geo_filter_limit',
        'has_fallback_url', 'has_custom_alias', 'has_qr_code',
        'has_export_csv', 'has_audit_report', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'has_fallback_url' => 'boolean',
        'has_custom_alias' => 'boolean',
        'has_qr_code' => 'boolean',
        'has_export_csv' => 'boolean',
        'has_audit_report' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function formattedPrice(): string
    {
        if ((int) $this->price === 0) {
            return 'Rp0';
        }

        return 'Rp' . number_format((int) $this->price, 0, ',', '.');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
