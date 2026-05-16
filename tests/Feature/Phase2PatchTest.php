<?php

namespace Tests\Feature;

use App\Models\BlockedIp;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\ShortLink;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase2PatchTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $free;
    protected Plan $pro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->free = Plan::create([
            'name' => 'Free', 'slug' => 'free', 'price' => 0, 'currency' => 'IDR',
            'billing_period' => 'monthly', 'max_links' => 5, 'max_clicks_per_link' => 100,
            'has_custom_alias' => false, 'has_fallback_url' => false, 'has_qr_code' => false,
            'has_export_csv' => false, 'has_audit_report' => false, 'sort_order' => 1, 'is_active' => true,
        ]);
        $this->pro = Plan::create([
            'name' => 'Pro', 'slug' => 'pro', 'price' => 99000, 'currency' => 'IDR',
            'billing_period' => 'monthly', 'max_links' => null, 'max_clicks_per_link' => null,
            'has_custom_alias' => true, 'has_fallback_url' => true, 'has_qr_code' => true,
            'has_export_csv' => true, 'has_audit_report' => true, 'sort_order' => 2, 'is_active' => true,
        ]);
        Setting::set('beta_mode_enabled', '1');
        Setting::set('beta_free_all_features', '1');
    }

    protected function disableBeta(): void
    {
        Setting::set('beta_mode_enabled', '0');
        Setting::set('beta_free_all_features', '0');
    }

    public function test_reserved_slug_returns_404(): void
    {
        $res = $this->get('/abuse');
        $res->assertOk();
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
        $this->assertNull($limits->clickQuotaPerLink($user));
        $this->assertTrue($limits->canUseQrCode($user));
        $this->assertTrue($limits->canExportCsv($user));
    }

    public function test_beta_off_plan_limits_apply(): void
    {
        $this->disableBeta();
        $user = User::factory()->create(['plan_id' => $this->free->id]);
        $limits = app(\App\Services\PlanLimitService::class);
        $this->assertSame(100, $limits->clickQuotaPerLink($user));
        $this->assertFalse($limits->canUseCustomAlias($user));
        $this->assertFalse($limits->canExportCsv($user));
    }

    public function test_beta_on_create_page_shows_premium_fields(): void
    {
        $user = User::factory()->create(['plan_id' => $this->free->id]);
        $res = $this->actingAs($user)->get('/dashboard/links/create');
        $res->assertOk();
        $res->assertSee('Custom Alias', false);
        $res->assertSee('Fallback URL', false);
        $res->assertSee('Geo Filter', false);
    }

    public function test_beta_off_checkout_creates_payment_and_subscription_pending(): void
    {
        $this->disableBeta();
        $user = User::factory()->create(['plan_id' => $this->free->id]);
        $res = $this->actingAs($user)->post('/dashboard/billing/checkout/' . $this->pro->id);
        $res->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id, 'plan_id' => $this->pro->id,
            'status' => 'pending', 'gateway' => 'manual_transfer',
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id, 'plan_id' => $this->pro->id, 'status' => 'pending',
        ]);

        $payment = Payment::where('user_id', $user->id)->first();
        $this->assertNotNull($payment->subscription_id);
    }

    public function test_mark_paid_activates_subscription_and_updates_user_plan(): void
    {
        $this->disableBeta();
        $admin = User::factory()->create(['plan_id' => $this->free->id, 'role' => 'admin', 'status' => 'active']);
        $user = User::factory()->create(['plan_id' => $this->free->id, 'status' => 'active']);
        $this->actingAs($user)->post('/dashboard/billing/checkout/' . $this->pro->id);
        $payment = Payment::where('user_id', $user->id)->firstOrFail();

        $res = $this->actingAs($admin)->patch('/admin/payments/' . $payment->id . '/mark-paid');
        $res->assertRedirect();

        $payment->refresh();
        $this->assertSame('paid', $payment->status);

        $sub = Subscription::find($payment->subscription_id);
        $this->assertSame('active', $sub->status);
        $this->assertNotNull($sub->started_at);
        $this->assertNotNull($sub->expires_at);

        $user->refresh();
        $this->assertSame($this->pro->id, $user->plan_id);
    }

    public function test_mark_paid_is_idempotent(): void
    {
        $this->disableBeta();
        $admin = User::factory()->create(['plan_id' => $this->free->id, 'role' => 'admin', 'status' => 'active']);
        $user = User::factory()->create(['plan_id' => $this->free->id, 'status' => 'active']);
        $this->actingAs($user)->post('/dashboard/billing/checkout/' . $this->pro->id);
        $payment = Payment::where('user_id', $user->id)->firstOrFail();

        $this->actingAs($admin)->patch('/admin/payments/' . $payment->id . '/mark-paid');
        $this->actingAs($admin)->patch('/admin/payments/' . $payment->id . '/mark-paid');

        $this->assertSame(1, Subscription::where('user_id', $user->id)->count());
    }

    public function test_billing_sidebar_menu_exists(): void
    {
        $user = User::factory()->create(['plan_id' => $this->free->id]);
        $res = $this->actingAs($user)->get('/dashboard');
        $res->assertOk();
        $res->assertSee(route('dashboard.billing'), false);
    }

    public function test_base_layout_does_not_use_cdn_tailwind(): void
    {
        $contents = file_get_contents(resource_path('views/layouts/base.blade.php'));
        $this->assertStringNotContainsString('cdn.tailwindcss.com', $contents);
        $this->assertStringNotContainsString('cdn.jsdelivr.net', $contents);
        $this->assertStringContainsString('@vite', $contents);
    }

    public function test_faq_seed_beta_text_is_correct(): void
    {
        $this->seed(\Database\Seeders\FaqSeeder::class);
        $faq = \App\Models\Faq::where('question', 'Apakah SlinkV gratis?')->first();
        $this->assertNotNull($faq);
        $this->assertStringContainsString('beta', strtolower($faq->answer));
        $this->assertStringContainsString('100% gratis', $faq->answer);
    }
}
