<?php

namespace Tests\Feature;

use App\Models\BlockedIp;
use App\Models\BotRule;
use App\Models\ClickLog;
use App\Models\Plan;
use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLogViewTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ShortLink $link;

    protected function setUp(): void
    {
        parent::setUp();

        Plan::create([
            'name' => 'Free', 'slug' => 'free', 'price' => 0, 'currency' => 'IDR',
            'billing_period' => 'monthly', 'max_links' => 5, 'max_clicks_per_link' => 100,
            'has_custom_alias' => false, 'has_fallback_url' => false, 'has_qr_code' => false,
            'has_export_csv' => false, 'has_audit_report' => false, 'sort_order' => 1, 'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.local',
            'password' => bcrypt('secret123'), 'role' => 'admin', 'status' => 'active',
        ]);

        $user = User::create([
            'name' => 'Owner', 'email' => 'owner@test.local',
            'password' => bcrypt('secret123'), 'role' => 'user', 'status' => 'active',
        ]);

        $this->link = ShortLink::create([
            'user_id' => $user->id, 'slug' => 'log-slug',
            'destination_url' => 'https://example.com/page',
            'device_filter' => 'all', 'is_active' => true,
        ]);
    }

    protected function makeLog(array $overrides = []): ClickLog
    {
        return ClickLog::create(array_merge([
            'short_link_id'   => $this->link->id,
            'ip_hash'         => hash('sha256', '1.2.3.4'),
            'user_agent'      => 'Mozilla/5.0 TestBot/1.0',
            'country_code'    => 'ID',
            'source_platform' => 'direct',
            'is_bot'          => false,
            'bot_score'       => 10,
            'bot_reasons'     => [],
            'action'          => 'redirected',
            'clicked_at'      => now(),
        ], $overrides));
    }

    public function test_click_logs_page_renders_without_legacy_field_errors(): void
    {
        $this->makeLog(['action' => 'blocked']);
        $this->makeLog(['action' => 'redirected', 'source_platform' => 'fb']);

        $this->actingAs($this->admin)
            ->get('/admin/click-logs')
            ->assertOk()
            ->assertSee('log-slug')
            ->assertDontSee('Undefined property');
    }

    public function test_bot_logs_page_renders_with_bot_reasons_array(): void
    {
        $this->makeLog([
            'is_bot' => true, 'bot_score' => 85,
            'bot_reasons' => ['ua_match', 'no_referer'],
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/bot-logs')
            ->assertOk()
            ->assertSee('log-slug')
            ->assertSee('ua_match')
            ->assertDontSee('Undefined property');
    }

    public function test_block_ip_from_click_log_creates_blocked_ip(): void
    {
        $log = $this->makeLog();

        $this->actingAs($this->admin)
            ->post('/admin/click-logs/' . $log->id . '/block-ip')
            ->assertRedirect();

        $this->assertDatabaseHas('blocked_ips', [
            'ip_hash' => $log->ip_hash, 'is_active' => true,
        ]);
    }

    public function test_block_ip_from_bot_log_creates_blocked_ip(): void
    {
        $log = $this->makeLog(['is_bot' => true, 'bot_score' => 90]);

        $this->actingAs($this->admin)
            ->post('/admin/bot-logs/' . $log->id . '/block-ip')
            ->assertRedirect();

        $this->assertDatabaseHas('blocked_ips', [
            'ip_hash' => $log->ip_hash,
        ]);
    }

    public function test_create_ua_rule_from_bot_log(): void
    {
        $log = $this->makeLog(['is_bot' => true, 'bot_score' => 90]);

        $this->actingAs($this->admin)
            ->post('/admin/bot-logs/' . $log->id . '/create-ua-rule')
            ->assertRedirect();

        $this->assertDatabaseHas('bot_rules', [
            'type'      => 'user_agent_contains',
            'is_active' => true,
        ]);
        $this->assertTrue(BotRule::where('pattern', 'like', '%TestBot%')->exists());
    }
}
