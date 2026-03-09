<?php
/**
 * CurrencyService.php
 *
 * @copyright  2022 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     guangda <service@guangda.work>
 * @created    2022-07-28 16:29:54
 * @modified   2022-07-28 16:29:54
 */

namespace Beike\Services;

use Beike\Repositories\CurrencyRepo;
use Illuminate\Support\Facades\Http;

class CurrencyService
{
    private static $instance;

    private $currencies = [];

    public function __construct()
    {
        foreach (CurrencyRepo::listEnabled() as $result) {
            $this->currencies[$result->code] = $result;
        }
    }

    public static function getInstance()
    {
        if (! self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function clearInstance(): void
    {
        self::$instance = null;
    }

    public function format($amount, $currency, $value = '', $format = true)
    {
        $currency = normalize_currency_code($currency);

        if (empty($this->currencies)) {
            return $amount;
        }

        if (!isset($this->currencies[$currency])) {
            throw (new \Exception("Currency {$currency} not exist. Please contact the administrator to create it"));
        }

        $currencyRow = $this->currencies[$currency] ?? null;
        if (empty($currencyRow)) {
            return $amount;
        }

        $symbol_left   = $currencyRow->symbol_left;
        $symbol_right  = $currencyRow->symbol_right;
        $decimal_place = $currencyRow->decimal_place;

        if (! $value) {
            $value = $currencyRow->value;
        }

        $amount = $value ? (float) $amount * $value : (float) $amount;

        $amount = round($amount, (int) $decimal_place);

        if (! $format) {
            return $amount;
        }

        $string = '';
        if ($amount < 0) {
            $string = '-';
        }

        if ($symbol_left) {
            $string .= $symbol_left;
        }

        $string .= number_format(abs($amount), (int) $decimal_place, __('currency.decimal_point'), __('currency.thousand_point'));

        if ($symbol_right) {
            $string .= ' ' . $symbol_right;
        }

        return $string;
    }

    public function convert($value, $from, $to)
    {
        $from = normalize_currency_code($from);
        $to   = normalize_currency_code($to);

        if (isset($this->currencies[$from])) {
            $from = $this->currencies[$from]->value;
        } else {
            $from = 1;
        }

        if (isset($this->currencies[$to])) {
            $to = $this->currencies[$to]->value;
        } else {
            $to = 1;
        }

        return $value * ($to / $from);
    }

    public function getRatesFromApi(string $date)
    {
        $baseCurrency = external_currency_code(system_setting('base.currency', 'USD'));
        $cacheKey     = 'currency:rates:' . $date . ':' . $baseCurrency;
        if ($rates = cache()->get($cacheKey)) {
            return $rates;
        }
        if (empty(system_setting('base.rate_api_key'))) {
            return [];
        }
        $data = Http::get(
            sprintf(
                'https://v6.exchangerate-api.com/v6/%s/latest/%s',
                system_setting('base.rate_api_key'),
                $baseCurrency
            )
        )->json();
        $rates = collect($data['conversion_rates'] ?? [])->mapWithKeys(function ($value, $code) {
            return [normalize_currency_code($code) => $value];
        })->all();
        cache()->set($cacheKey, $rates);

        return $rates;
    }
}
