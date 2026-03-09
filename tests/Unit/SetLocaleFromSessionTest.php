<?php

namespace Tests\Unit;

use App\Http\Middleware\SetLocaleFromSession;
use Tests\TestCase;

class SetLocaleFromSessionTest extends TestCase
{
    public function test_resolve_default_locale_falls_back_to_first_enabled_language_when_setting_is_missing(): void
    {
        config([
            'bk.system.base.locale' => null,
            'app.locale' => 'zh_cn',
            'app.fallback_locale' => 'fr',
        ]);

        $middleware = new SetLocaleFromSession;
        $method = new \ReflectionMethod($middleware, 'resolveDefaultLocale');

        $this->assertSame('vi', $method->invoke($middleware, ['vi', 'en']));
    }

    public function test_resolve_default_locale_prefers_enabled_configured_locale(): void
    {
        config([
            'bk.system.base.locale' => 'en',
            'app.locale' => 'zh_cn',
            'app.fallback_locale' => 'fr',
        ]);

        $middleware = new SetLocaleFromSession;
        $method = new \ReflectionMethod($middleware, 'resolveDefaultLocale');

        $this->assertSame('en', $method->invoke($middleware, ['vi', 'en']));
    }
}
