<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\ShortLink;
use App\Models\User;

class PlanLimitService
{
    public function __construct(private BetaModeService $beta) {}

    public function planFor(User $user): Plan
    {
        return $user->effectivePlan()
            ?? Plan::firstWhere('slug', 'free')
            ?? new Plan([
                'name' => 'Free', 'slug' => 'free', 'price' => 0,
                'max_links' => 5, 'max_clicks_per_link' => 1000,
                'analytics_retention_days' => 7, 'bot_protection_level' => 'basic',
                'geo_filter_limit' => 0, 'has_fallback_url' => false,
                'has_custom_alias' => false, 'has_qr_code' => true,
                'has_export_csv' => false, 'has_audit_report' => false,
            ]);
    }

    public function isBetaFree(): bool
    {
        return $this->beta->isFreeAllFeatures();
    }

    public function canCreateLink(User $user): bool
    {
        if ($this->isBetaFree()) return true;
        $plan = $this->planFor($user);
        if ($plan->max_links === null) return true;
        $active = ShortLink::where('user_id', $user->id)->where('is_active', true)->count();
        return $active < (int) $plan->max_links;
    }

    public function activeLinkLimit(User $user): ?int
    {
        if ($this->isBetaFree()) return null;
        return $this->planFor($user)->max_links;
    }

    public function clickQuotaPerLink(User $user): ?int
    {
        if ($this->isBetaFree()) return null;
        return $this->planFor($user)->max_clicks_per_link;
    }

    public function analyticsRetention(User $user): int
    {
        if ($this->isBetaFree()) return 365;
        return (int) $this->planFor($user)->analytics_retention_days;
    }

    public function botProtectionLevel(User $user): string
    {
        if ($this->isBetaFree()) return 'advanced';
        return (string) $this->planFor($user)->bot_protection_level;
    }

    public function canUseCustomAlias(User $user): bool
    {
        return $this->isBetaFree() || (bool) $this->planFor($user)->has_custom_alias;
    }

    public function canUseFallback(User $user): bool
    {
        return $this->isBetaFree() || (bool) $this->planFor($user)->has_fallback_url;
    }

    public function canUseQrCode(User $user): bool
    {
        return $this->isBetaFree() || (bool) $this->planFor($user)->has_qr_code;
    }

    public function canExportCsv(User $user): bool
    {
        return $this->isBetaFree() || (bool) $this->planFor($user)->has_export_csv;
    }

    public function canUseAuditReport(User $user): bool
    {
        return $this->isBetaFree() || (bool) $this->planFor($user)->has_audit_report;
    }

    public function canUseGeoFilter(User $user): bool
    {
        if ($this->isBetaFree()) return true;
        $limit = $this->planFor($user)->geo_filter_limit;
        return $limit === null || $limit > 0;
    }

    public function canUseDeviceFilter(User $user): bool
    {
        return $this->isBetaFree() || $this->planFor($user)->bot_protection_level !== 'none';
    }

    public function geoFilterLimit(User $user): ?int
    {
        if ($this->isBetaFree()) return null;
        return $this->planFor($user)->geo_filter_limit;
    }
}
