<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\ShortLink;
use App\Models\Subscription;
use App\Services\BetaModeService;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:expire {--dry-run : Tampilkan saja tanpa mengubah}';
    protected $description = 'Expire active subscriptions yang sudah lewat tanggal, dan downgrade user ke Free (kecuali beta free-all-features aktif).';

    public function handle(BetaModeService $betaSvc): int
    {
        $beta = $betaSvc->isFreeAllFeatures();
        $dry = (bool) $this->option('dry-run');

        $now = now();
        $expired = Subscription::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->with('user')
            ->get();

        $this->info("Found {$expired->count()} expired subscription(s). Beta=" . ($beta ? 'on' : 'off') . ($dry ? ' [dry-run]' : ''));

        $freePlan = Plan::where('slug', 'free')->orderBy('id')->first()
            ?? Plan::where('price', 0)->orderBy('id')->first();

        $downgraded = 0;
        $deactivated = 0;

        foreach ($expired as $sub) {
            if (!$dry) {
                $sub->update(['status' => 'expired']);
            }

            if ($beta) {
                continue; // Beta mode: jangan downgrade.
            }

            $user = $sub->user;
            if (!$user) continue;

            if ($freePlan && $user->plan_id !== $freePlan->id) {
                if (!$dry) {
                    $user->update(['plan_id' => $freePlan->id]);
                }
                $downgraded++;
            }

            // Deaktifkan link aktif yang melebihi kuota Free
            $maxLinks = (int) ($freePlan->max_links ?? 5);
            if ($maxLinks > 0) {
                $activeIds = ShortLink::where('user_id', $user->id)
                    ->where('is_active', true)
                    ->orderBy('created_at')
                    ->pluck('id');
                if ($activeIds->count() > $maxLinks) {
                    $toDeactivate = $activeIds->take($activeIds->count() - $maxLinks);
                    if (!$dry) {
                        ShortLink::whereIn('id', $toDeactivate)->update(['is_active' => false]);
                    }
                    $deactivated += $toDeactivate->count();
                }
            }
        }

        $this->info("Expired: {$expired->count()} | Downgraded: {$downgraded} | Links deactivated: {$deactivated}");
        return self::SUCCESS;
    }
}
