<?php

namespace Tests\Feature;

use Beike\Models\Currency;
use Beike\Models\ProductSku;
use Beike\Models\Setting;
use Beike\Repositories\CurrencyRepo;
use Beike\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class CurrencyVndSupportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CurrencyRepo::flushEnabled();
        CurrencyService::clearInstance();
    }

    public function test_currency_format_supports_internal_and_external_vnd_codes(): void
    {
        Currency::query()->create([
            'name' => 'Việt Nam Đồng',
            'code' => 'VNĐ',
            'symbol_left' => '',
            'symbol_right' => 'đ',
            'decimal_place' => 0,
            'value' => 1,
            'status' => 1,
        ]);

        $this->assertSame('1,235 đ', currency_format(1234.56, 'VNĐ'));
        $this->assertSame('1,235 đ', currency_format(1234.56, 'VND'));
    }

    public function test_current_currency_code_normalizes_legacy_vnd_session_value(): void
    {
        Session::put('currency', 'VND');

        $this->assertSame('VNĐ', current_currency_code());
    }

    public function test_exchange_rate_api_uses_external_vnd_code_and_normalizes_response(): void
    {
        config([
            'bk.system.base.currency' => 'VNĐ',
            'bk.system.base.rate_api_key' => 'demo-key',
        ]);

        Http::fake([
            'https://v6.exchangerate-api.com/v6/demo-key/latest/VND' => Http::response([
                'conversion_rates' => [
                    'VND' => 1,
                    'USD' => 0.000039,
                ],
            ]),
        ]);

        $rates = CurrencyService::getInstance()->getRatesFromApi('2026-03-09');

        $this->assertSame(1, $rates['VNĐ']);
        $this->assertSame(0.000039, $rates['USD']);
    }

    public function test_catalog_migration_converts_prices_and_switches_default_currency(): void
    {
        Setting::query()->create([
            'type' => 'system',
            'space' => 'base',
            'name' => 'currency',
            'value' => 'USD',
            'json' => 0,
        ]);
        Setting::query()->create([
            'type' => 'system',
            'space' => 'base',
            'name' => 'rate_api_key',
            'value' => 'demo-key',
            'json' => 0,
        ]);

        Currency::query()->create([
            'name' => 'USD',
            'code' => 'USD',
            'symbol_left' => '$',
            'symbol_right' => '',
            'decimal_place' => 2,
            'value' => 1,
            'status' => 1,
        ]);
        Currency::query()->create([
            'name' => 'EUR',
            'code' => 'EUR',
            'symbol_left' => '€',
            'symbol_right' => '',
            'decimal_place' => 2,
            'value' => 0.92,
            'status' => 1,
        ]);

        ProductSku::query()->create([
            'product_id' => 1,
            'variants' => null,
            'position' => 0,
            'images' => null,
            'model' => '',
            'sku' => 'SKU-1',
            'price' => 10,
            'origin_price' => 12,
            'cost_price' => 4,
            'quantity' => 5,
            'is_default' => true,
        ]);

        Http::fake([
            'https://v6.exchangerate-api.com/v6/demo-key/latest/USD' => Http::response([
                'conversion_rates' => [
                    'USD' => 1,
                    'VND' => 25000,
                    'EUR' => 0.92,
                ],
            ]),
        ]);

        $migration = require base_path('database/migrations/2026_03_09_120000_migrate_catalog_prices_to_vnd.php');
        $migration->up();

        $sku = ProductSku::query()->firstOrFail();

        $this->assertSame(250000.0, (float) $sku->price);
        $this->assertSame(300000.0, (float) $sku->origin_price);
        $this->assertSame(100000.0, (float) $sku->cost_price);
        $this->assertDatabaseHas('settings', [
            'type' => 'system',
            'space' => 'base',
            'name' => 'currency',
            'value' => 'VNĐ',
        ]);
        $this->assertDatabaseHas('currencies', [
            'code' => 'VNĐ',
            'value' => 1,
            'decimal_place' => '0',
        ]);
    }
}
