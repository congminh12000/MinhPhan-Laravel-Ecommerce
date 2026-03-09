<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings') || ! Schema::hasTable('currencies')) {
            return;
        }

        $baseCurrency = DB::table('settings')
            ->where('type', 'system')
            ->where('space', 'base')
            ->where('name', 'currency')
            ->value('value');

        if (! $baseCurrency) {
            return;
        }

        if (normalize_currency_code($baseCurrency) === 'VNĐ') {
            $this->ensureVndCurrencyRow();
            $this->updateBaseCurrencySetting();

            return;
        }

        if (! Schema::hasTable('product_skus')) {
            return;
        }

        $rateApiKey = (string) DB::table('settings')
            ->where('type', 'system')
            ->where('space', 'base')
            ->where('name', 'rate_api_key')
            ->value('value');

        if ($rateApiKey === '') {
            throw new RuntimeException('The VND catalog migration requires base.rate_api_key to be configured.');
        }

        $rates   = $this->fetchRatesSnapshot($rateApiKey, $baseCurrency);
        $vndRate = $rates['VNĐ'] ?? null;

        if (! is_numeric($vndRate) || (float) $vndRate <= 0) {
            throw new RuntimeException('Unable to resolve the VND exchange rate for the catalog migration.');
        }

        DB::transaction(function () use ($rates, $vndRate) {
            $this->ensureVndCurrencyRow();
            $this->convertProductSkuPrices((float) $vndRate);
            $this->recalculateCurrencyValues($rates, (float) $vndRate);
            $this->updateBaseCurrencySetting();
        });
    }

    public function down(): void
    {
    }

    private function fetchRatesSnapshot(string $rateApiKey, string $baseCurrency): array
    {
        $response = Http::get(sprintf(
            'https://v6.exchangerate-api.com/v6/%s/latest/%s',
            $rateApiKey,
            external_currency_code($baseCurrency)
        ));

        if (! $response->successful()) {
            throw new RuntimeException('The exchange-rate API request failed during the VND catalog migration.');
        }

        return collect($response->json('conversion_rates', []))->mapWithKeys(function ($value, $code) {
            return [normalize_currency_code($code) => (float) $value];
        })->all();
    }

    private function ensureVndCurrencyRow(): void
    {
        $timestamp = now();
        $legacyVnd = DB::table('currencies')->where('code', 'VND')->orderBy('id')->first();
        $vndRow    = DB::table('currencies')->where('code', 'VNĐ')->orderBy('id')->first();

        if ($legacyVnd && ! $vndRow) {
            DB::table('currencies')->where('id', $legacyVnd->id)->update([
                'name'          => 'Việt Nam Đồng',
                'code'          => 'VNĐ',
                'symbol_left'   => '',
                'symbol_right'  => 'đ',
                'decimal_place' => 0,
                'value'         => 1,
                'status'        => 1,
                'updated_at'    => $timestamp,
            ]);

            return;
        }

        DB::table('currencies')->updateOrInsert(
            ['code' => 'VNĐ'],
            [
                'name'          => 'Việt Nam Đồng',
                'symbol_left'   => '',
                'symbol_right'  => 'đ',
                'decimal_place' => 0,
                'value'         => 1,
                'status'        => 1,
                'updated_at'    => $timestamp,
                'created_at'    => $timestamp,
            ]
        );

        DB::table('currencies')->where('code', 'VND')->delete();
    }

    private function convertProductSkuPrices(float $vndRate): void
    {
        DB::table('product_skus')->select(['id', 'price', 'origin_price', 'cost_price'])->orderBy('id')->chunkById(100, function ($skus) use ($vndRate) {
            foreach ($skus as $sku) {
                DB::table('product_skus')->where('id', $sku->id)->update([
                    'price'        => $this->convertAmount($sku->price, $vndRate),
                    'origin_price' => $this->convertAmount($sku->origin_price, $vndRate),
                    'cost_price'   => $this->convertAmount($sku->cost_price, $vndRate),
                    'updated_at'   => now(),
                ]);
            }
        });
    }

    private function recalculateCurrencyValues(array $rates, float $vndRate): void
    {
        DB::table('currencies')->orderBy('id')->get()->each(function ($currency) use ($rates, $vndRate) {
            $normalizedCode = normalize_currency_code($currency->code);
            $nextValue      = $normalizedCode === 'VNĐ'
                ? 1
                : (($rates[$normalizedCode] ?? null) ? (float) $rates[$normalizedCode] / $vndRate : $currency->value);

            DB::table('currencies')->where('id', $currency->id)->update([
                'code'       => $normalizedCode,
                'value'      => $nextValue,
                'updated_at' => now(),
            ]);
        });
    }

    private function updateBaseCurrencySetting(): void
    {
        DB::table('settings')
            ->where('type', 'system')
            ->where('space', 'base')
            ->where('name', 'currency')
            ->update([
                'value'      => 'VNĐ',
                'updated_at' => now(),
            ]);
    }

    private function convertAmount($amount, float $rate): float
    {
        return round((float) $amount * $rate, 0);
    }
};
