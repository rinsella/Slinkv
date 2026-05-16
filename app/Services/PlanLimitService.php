<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\ShortLink;
use App\Models\User;

class PlanLimitService
{
    public function planFor(User $user): Plan
    {
        return $user->effectivePlan() ?? Plan::firstWhere('slug', 'free') ?? new Plan([
            'name' => 'Free', 'slug' => 'free', 'max_links' => null,
            'max_clicks_per_link' => null, 'analytics_retention_days' => 365,
            'bot_protection_level' => 'advanced', 'has_fallback_url' => true,
            'has_custom_alias' => true, 'has_qr_code' => true,
            'has_export_csv' => true, 'has_audit_report' => true,
            'geo_filter_limit' => null,
        ]);
    }

    public function canCreateLink(User $user): bool
    {
        $plan = $this->planFor($user);
        if ($plan->max_links === null) return true;
        $active = ShortLink::where('user_id', $user->id)->where('is_active', true)->count();
        return $active < (int) $plan->max_links;
    }

    public function activeLinkLimit(User $user): ?int
    {
        return $this->planFor($user)->max_links;
    }

    public function clickQuotaPerLink(User $user): ?int
    {
        return $this->planFor($user)->max_clicks_per_link;
    }

    public function analyticsRetention(User $user): int
    {
        return (int) $this->planFor($user)->analytics_retention_days;
    }

    public function canUseCustomAlias(User $user): bool
    {
        return (bool) $this->planFor($user)->has_custom_alias;
    }

    public function canUseFallback(User $user): bool
    {
        return (bool) $this->planFor($user)->has_fallback_url;
    }

    public function geoFilterLimit(User $user): ?int
    {
        return $this->planFor($user)->geo_filter_limit;
    }
}
