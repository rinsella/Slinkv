<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;

class BetaModeService
{
    public function isEnabled(): bool
    {
        return (string) Setting::get('beta_mode_enabled', '1') === '1';
    }

    public function isFreeAllFeatures(): bool
    {
        return $this->isEnabled() && (string) Setting::get('beta_free_all_features', '1') === '1';
    }

    public function shouldShowBanner(): bool
    {
        return $this->isEnabled() && (string) Setting::get('beta_banner_enabled', '1') === '1';
    }

    public function announcementText(): string
    {
        return (string) Setting::get(
            'beta_announcement_text',
            'Selama masa beta, semua fitur SlinkV tersedia 100% gratis untuk semua pengguna.'
        );
    }

    public function endsAt(): ?Carbon
    {
        $v = Setting::get('beta_ends_at');
        if (!$v) return null;
        try { return Carbon::parse($v); } catch (\Throwable) { return null; }
    }

    public function badgeLabel(): string
    {
        return $this->isFreeAllFeatures() ? 'FREE BETA' : 'FREE';
    }
}
