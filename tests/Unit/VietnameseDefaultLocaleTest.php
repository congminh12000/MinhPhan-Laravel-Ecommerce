<?php

namespace Tests\Unit;

use Database\Seeders\LanguagesSeeder;
use Database\Seeders\SettingsSeeder;
use Tests\TestCase;

class VietnameseDefaultLocaleTest extends TestCase
{
    public function test_app_defaults_to_vietnamese_locale(): void
    {
        $this->assertSame('vi', config('app.locale'));
        $this->assertSame('vi', config('app.fallback_locale'));
        $this->assertContains('vi', config('app.langs'));
    }

    public function test_language_seeder_contains_enabled_vietnamese_language(): void
    {
        $items = (new LanguagesSeeder)->getItems();
        $vietnamese = collect($items)->firstWhere('code', 'vi');

        $this->assertNotNull($vietnamese);
        $this->assertSame('Tiếng Việt', $vietnamese['name']);
        $this->assertSame('vi_VN', $vietnamese['locale']);
        $this->assertSame(1, $vietnamese['status']);
    }

    public function test_settings_seeder_uses_vietnamese_as_default_language(): void
    {
        $items = (new SettingsSeeder)->getItems();
        $localeSetting = collect($items)->first(fn (array $item) => $item['type'] === 'system' && $item['space'] === 'base' && $item['name'] === 'locale');

        $this->assertNotNull($localeSetting);
        $this->assertSame('vi', $localeSetting['value']);
    }
}
