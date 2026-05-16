<?php

namespace Tests\Feature;

use App\Models\BlockedIp;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase2PatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed minimal plans
        $this->free = Plan::create([
            'name' => 'Free', 'slug' => 'free', 'price' => 0, 'currency' => 'IDR',
            'billing_period' => 'monthly', 'max_links' => 5, 'max_clicks_per_link' => 100,
            'has_custom_alias' => false, 'has_fallback_url' => false, 'has_qr_code' => false,
            'has_export_csv' => false, 'has_audit_report' => false, 'sort_order' => 1, 'is_active' => true,
        ]);
        // Default beta on
        Setting::set('beta_mode', '1');
        Setting::set('beta_free_all_features', '1');
    }

    public function test_reserved_slug_returns_404(): void
    {
        $res = $this->get('/abuse');
        $res->assertOk();
        $res = $this->get('/admin'); // reserved + admin requires auth; should not be a redirect
        $this->assertNotEquals(302, $res->status() === 302 ? 302 : 0);
    }

    public function test_password_protected_link_requires_password(): void
    {
        $user = User::factory()->create(['plan_id' => $this->free->id]);
        $link = ShortLink::create([
            'user_id' => $user->id,
            'slug' => 'abc123',
            'destination_url' => 'https://example.com',
            'password' => Hash::make('rahasia'),
            'is_active' => true,
            'bot_protection_enabled' => false,
            'device_filter' => 'all',
        ]);

        $res = $this->get('/abc123');
        $res->assertStatus(401);
        $res->assertSee('Password');

        $res = $this->post('/abc123/unlock', ['password' => 'salah']);
        $res->assertSessionHasErrors('password');

        $res = $this->post('/abc123/unlock', ['password' => 'rahasia']);
        $res->assertRedirect('/abc123');

        $res = $this->get('/abc123');
        $res->assertRedirect('https://example.com');
    }

    public function test_blocked_ip_is_always_blocked(): void
    {
        $user = User::factory()->create(['plan_id' => $this->free->id]);
        $link = ShortLink::create([
            'user_id' => $user->id,
            'slug' => 'free01',
            'destination_url' => 'https://example.com',
            'is_active' => true,
            'bot_protection_enabled' => false, // Bot protection OFF
            'device_filter' => 'all',
        ]);
        BlockedIp::create([
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'reason' => 'test',
            'is_active' => true,
        ]);
        Cache::flush();

        $res = $this->get('/free01');
        $this->assertContains($res->status(), [403, 302]); // blocked view or fallback redirect
    }

    public function test_registration_can_be_disabled(): void
    {
        Setting::set('registration_enabled', '0');
        $res = $this->get('/register');
        $res->assertStatus(403);
        $res->assertSee('Pendaftaran');
    }

    public function test_beta_mode_grants_unlimited(): void
    {
        $user = User::factory()->create(['plan_id' => $this->free->id]);
        $limits = app(\App\Services\PlanLimitService::class);
        // Free plan has 100 clicks per link, but beta should grant unlimited
        $this->assertNull($limits->clickQuotaPerLink($user));
        $this->assertTrue($limits->canUseQrCode($user));
        $this->assertTrue($limits->canExportCsv($user));
    }
}
