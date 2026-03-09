<?php

namespace Plugin\Sepay\Services;

use Beike\Models\Order;
use Beike\Shop\Services\PaymentService;
use Illuminate\Support\Facades\Route;
use SePay\Builders\CheckoutBuilder;
use SePay\SePayClient;

class SepayService extends PaymentService
{
    private const PAYMENT_METHOD = 'BANK_TRANSFER';

    private SePayClient $client;

    public function __construct($order)
    {
        parent::__construct($order);
        $this->guardConfiguration();
        $this->guardCurrency();
        $this->client = new SePayClient(
            (string) plugin_setting('sepay.merchant_id'),
            (string) plugin_setting('sepay.secret_key'),
            $this->getEnvironment(),
        );
    }

    public function getCheckoutActionUrl(): string
    {
        return $this->client->checkout()->getCheckoutUrl($this->getEnvironment());
    }

    public function getCheckoutFormFields(): array
    {
        return $this->client->checkout()->generateFormFields($this->buildCheckoutData());
    }

    public static function getReturnData(string $type): array
    {
        $messages = [
            'success' => [
                'title'   => trans('Sepay::common.return_success_title'),
                'message' => trans('Sepay::common.return_success_message'),
                'alert'   => 'success',
            ],
            'error'   => [
                'title'   => trans('Sepay::common.return_error_title'),
                'message' => trans('Sepay::common.return_error_message'),
                'alert'   => 'danger',
            ],
            'cancel'  => [
                'title'   => trans('Sepay::common.return_cancel_title'),
                'message' => trans('Sepay::common.return_cancel_message'),
                'alert'   => 'warning',
            ],
        ];

        return $messages[$type] ?? $messages['error'];
    }

    private function buildCheckoutData(): array
    {
        $order = $this->order;

        return CheckoutBuilder::make()
            ->currency(external_currency_code($order->currency_code))
            ->orderInvoiceNumber((string) $order->number)
            ->orderAmount((int) round((float) $order->total))
            ->operation('PURCHASE')
            ->paymentMethod(self::PAYMENT_METHOD)
            ->orderDescription($this->buildOrderDescription())
            ->customerId($this->buildCustomerId())
            ->successUrl($this->buildReturnUrl('success'))
            ->errorUrl($this->buildReturnUrl('error'))
            ->cancelUrl($this->buildReturnUrl('cancel'))
            ->build();
    }

    private function buildOrderDescription(): string
    {
        return sprintf('Thanh toan don hang %s', $this->order->number);
    }

    private function buildCustomerId(): string
    {
        if ($this->order->customer_id) {
            return (string) $this->order->customer_id;
        }

        return 'guest-' . $this->order->number;
    }

    private function buildReturnUrl(string $type): string
    {
        $query = http_build_query([
            'order_number' => $this->order->number,
            'email'        => $this->order->email,
        ]);

        if (Route::has("shop.sepay.return.{$type}")) {
            return shop_route("sepay.return.{$type}", [
                'order_number' => $this->order->number,
                'email'        => $this->order->email,
            ]);
        }

        return url("/sepay/return/{$type}?{$query}");
    }

    private function guardCurrency(): void
    {
        if (! is_vnd_currency($this->order->currency_code)) {
            throw new \Exception(trans('Sepay::common.invalid_currency'));
        }
    }

    private function guardConfiguration(): void
    {
        if (! plugin_setting('sepay.merchant_id') || ! plugin_setting('sepay.secret_key')) {
            throw new \Exception(trans('Sepay::common.invalid_configuration'));
        }
    }

    private function getEnvironment(): string
    {
        $environment = (string) plugin_setting('sepay.environment', SePayClient::ENVIRONMENT_SANDBOX);

        return $environment === SePayClient::ENVIRONMENT_PRODUCTION
            ? SePayClient::ENVIRONMENT_PRODUCTION
            : SePayClient::ENVIRONMENT_SANDBOX;
    }
}
