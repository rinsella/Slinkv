<?php

namespace Tests\Feature;

use App\Models\AbuseReport;
use App\Models\Plan;
use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAbuseReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ShortLink $link;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
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
            'user_id' => $user->id, 'slug' => 'demo-slug',
            'destination_url' => 'https://example.com/demo',
            'device_filter' => 'all', 'is_active' => true,
        ]);
    }

    public function test_index_shows_report_without_relation_error(): void
    {
        AbuseReport::create([
            'reporter_email' => 'reporter@test.local',
            'short_link_id'  => $this->link->id,
            'reason'         => 'Spam phishing',
            'status'         => 'open',
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/abuse-reports')
            ->assertOk()
            ->assertSee('demo-slug')
            ->assertSee('reporter@test.local');
    }

    public function test_anonymous_report_renders_as_anonim(): void
    {
        AbuseReport::create([
            'reporter_email' => null,
            'short_link_id'  => $this->link->id,
            'reason'         => 'Anonymous abuse',
            'status'         => 'open',
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/abuse-reports')
            ->assertOk()
            ->assertSee('anonim');
    }

    public function test_show_page_loads_with_shortlink_relation(): void
    {
        $report = AbuseReport::create([
            'reporter_email' => 'r@test.local',
            'short_link_id'  => $this->link->id,
            'reason'         => 'Bad content',
            'status'         => 'open',
        ]);

        $this->actingAs($this->admin)
            ->get('/admin/abuse-reports/' . $report->id)
            ->assertOk()
            ->assertSee('demo-slug')
            ->assertSee('example.com/demo');
    }
}
