<?php

namespace Tests\Feature;

use Tests\TestCase;

class ViteReleaseTest extends TestCase
{
    public function test_site_webmanifest_has_apple_touch_icon(): void
    {
        $path = public_path('site.webmanifest');
        $this->assertFileExists($path);
        $json = json_decode(file_get_contents($path), true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('icons', $json);

        $hasApple = false;
        foreach ($json['icons'] as $icon) {
            if (isset($icon['src']) && str_contains($icon['src'], 'apple-touch-icon')) {
                $hasApple = true;
                break;
            }
        }
        $this->assertTrue($hasApple, 'site.webmanifest must reference apple-touch-icon');
    }

    public function test_health_label_mentions_npm_run_build_and_public_build(): void
    {
        $src = file_get_contents(base_path('app/Http/Controllers/Admin/AdminHealthController.php'));
        $this->assertStringContainsString('npm run build', $src);
        $this->assertStringContainsString('public/build', $src);
    }

    public function test_admin_log_blades_use_current_field_names(): void
    {
        $click = file_get_contents(base_path('resources/views/admin/click-logs.blade.php'));
        $bot = file_get_contents(base_path('resources/views/admin/bot-logs.blade.php'));

        foreach ([$click, $bot] as $blade) {
            $this->assertStringNotContainsString('short_code', $blade);
            $this->assertStringNotContainsString('original_url', $blade);
        }
        // bot_reasons (plural / array) is current; bare bot_reason is legacy.
        $this->assertDoesNotMatchRegularExpression('/bot_reason(?!s)/', $bot);
        $this->assertStringContainsString('slug', $click);
        $this->assertStringContainsString('slug', $bot);
    }
}
