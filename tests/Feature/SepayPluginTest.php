<?php

namespace Tests\Feature;

use Beike\Models\Order;
use Beike\Models\OrderPayment;
use Beike\Services\StateMachineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Plugin\Sepay\Controllers\SepayController;
use Plugin\Sepay\Services\SepayService;
use Tests\TestCase;

class SepayPluginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'bk.plugin.sepay.merchant_id' => 'SP-TEST-123456',
            'bk.plugin.sepay.secret_key' => 'spsk_test_123456',
            'bk.plugin.sepay.environment' => 'sandbox',
        ]);

        Route::name('shop.')->group(function () {
            Route::get('/sepay/return/success', static fn () => 'ok')->name('sepay.return.success');
            Route::get('/sepay/return/error', static fn () => 'ok')->name('sepay.return.error');
            Route::get('/sepay/return/cancel', static fn () => 'ok')->name('sepay.return.cancel');
            Route::get('/orders/{number}/pay', static fn () => 'ok')->name('orders.pay');
            Route::post('/callback/sepay', [SepayController::class, 'callback'])->name('sepay.callback');
            Route::post('/sepay/ipn', [SepayController::class, 'callback'])->name('sepay.ipn');
            Route::post('/api/sepay/webhook', [SepayController::class, 'callback'])->name('sepay.webhook');
        });
    }

    public function test_sepay_service_generates_vnd_bank_transfer_checkout_fields(): void
    {
        $order = new Order([
            'number' => 'ORD1001',
            'customer_id' => 12,
            'email' => 'buyer@example.com',
            'total' => 100000,
            'currency_code' => 'VNĐ',
            'status' => StateMachineService::UNPAID,
            'payment_method_code' => 'sepay',
        ]);

        $service = new SepayService($order);
        $fields = $service->getCheckoutFormFields();

        $this->assertSame('SP-TEST-123456', $fields['merchant']);
        $this->assertSame('BANK_TRANSFER', $fields['payment_method']);
        $this->assertSame('VND', $fields['currency']);
        $this->assertSame('ORD1001', $fields['order_invoice_number']);
        $this->assertArrayHasKey('signature', $fields);
        $this->assertStringContainsString('pay-sandbox.sepay.vn', $service->getCheckoutActionUrl());
    }

    public function test_callback_marks_order_paid_and_persists_payment_log(): void
    {
        Notification::fake();

        $order = $this->createOrder('SP2001');
        $response = $this->postJson('/callback/sepay', $this->validPayload($order->number), [
            'X-Secret-Key' => 'spsk_test_123456',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => StateMachineService::PAID,
        ]);
        $this->assertDatabaseHas('order_payments', [
            'order_id' => $order->id,
            'transaction_id' => 'TXN-001',
        ]);

        $payment = OrderPayment::query()->where('order_id', $order->id)->firstOrFail();
        $callback = json_decode($payment->callback, true);
        $result = json_decode($payment->response, true);

        $this->assertSame($order->number, data_get($callback, 'order.order_invoice_number'));
        $this->assertTrue($result['processed']);
    }

    public function test_callback_is_idempotent_for_already_paid_order(): void
    {
        Notification::fake();

        $order = $this->createOrder('SP2002');
        $payload = $this->validPayload($order->number);

        $this->postJson('/callback/sepay', $payload, [
            'X-Secret-Key' => 'spsk_test_123456',
        ]);
        $response = $this->postJson('/callback/sepay', $payload, [
            'X-Secret-Key' => 'spsk_test_123456',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, OrderPayment::query()->where('order_id', $order->id)->count());
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => StateMachineService::PAID,
        ]);
    }

    public function test_callback_rejects_invalid_secret_key(): void
    {
        $order = $this->createOrder('SP2003');
        $response = $this->postJson('/callback/sepay', $this->validPayload($order->number), [
            'X-Secret-Key' => 'wrong-secret',
        ]);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => StateMachineService::UNPAID,
        ]);
        $this->assertDatabaseMissing('order_payments', [
            'order_id' => $order->id,
        ]);
    }

    public function test_success_return_page_renders_order_info_without_missing_html_items(): void
    {
        $order = $this->createOrder('SP2004');
        $request = Request::create('/sepay/return/success', 'GET', [
            'order_number' => $order->number,
            'email' => $order->email,
        ]);

        $response = app(SepayController::class)->success($request);
        $data = $response->getData();

        $this->assertSame([], $data['html_items']);
        $this->assertSame($order->number, $data['order']->number);
        $this->assertSame('success', $data['type']);
    }

    public function test_ipn_alias_marks_order_paid(): void
    {
        Notification::fake();

        $order = $this->createOrder('SP2005');
        $response = $this->postJson('/sepay/ipn', $this->validPayload($order->number), [
            'X-Secret-Key' => 'spsk_test_123456',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => StateMachineService::PAID,
        ]);
    }

    public function test_webhook_alias_marks_order_paid(): void
    {
        Notification::fake();

        $order = $this->createOrder('SP2006');
        $response = $this->postJson('/api/sepay/webhook', $this->validPayload($order->number), [
            'X-Secret-Key' => 'spsk_test_123456',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => StateMachineService::PAID,
        ]);
    }

    private function createOrder(string $number): Order
    {
        return Order::query()->create([
            'number' => $number,
            'customer_id' => 0,
            'customer_group_id' => 1,
            'shipping_address_id' => 0,
            'payment_address_id' => 0,
            'customer_name' => 'Buyer',
            'email' => 'buyer@example.com',
            'calling_code' => 84,
            'telephone' => '0900000000',
            'total' => 100000,
            'locale' => 'en',
            'currency_code' => 'VNĐ',
            'currency_value' => '1',
            'ip' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'status' => StateMachineService::UNPAID,
            'shipping_method_code' => 'flat_shipping',
            'shipping_method_name' => 'Flat Shipping',
            'shipping_customer_name' => 'Buyer',
            'shipping_calling_code' => '84',
            'shipping_telephone' => '0900000000',
            'shipping_country' => 'Vietnam',
            'shipping_country_id' => 230,
            'shipping_zone' => 'HCM',
            'shipping_zone_id' => 1,
            'shipping_city' => 'Ho Chi Minh City',
            'shipping_address_1' => '123 Test Street',
            'shipping_zipcode' => '700000',
            'shipping_address_2' => 'Ward 1',
            'payment_method_code' => 'sepay',
            'payment_method_name' => 'SePay',
            'payment_customer_name' => 'Buyer',
            'payment_calling_code' => '84',
            'payment_telephone' => '0900000000',
            'payment_country' => 'Vietnam',
            'payment_country_id' => 230,
            'payment_zone' => 'HCM',
            'payment_zone_id' => 1,
            'payment_city' => 'Ho Chi Minh City',
            'payment_address_1' => '123 Test Street',
            'payment_address_2' => 'Ward 1',
            'payment_zipcode' => '700000',
        ]);
    }

    private function validPayload(string $orderNumber): array
    {
        return [
            'timestamp' => time(),
            'notification_type' => 'ORDER_PAID',
            'order' => [
                'id' => 'e2c195be-c721-47eb-b323-99ab24e52d85',
                'order_id' => 'SEPAY-ORDER-1',
                'order_status' => 'CAPTURED',
                'order_currency' => 'VND',
                'order_amount' => '100000',
                'order_invoice_number' => $orderNumber,
                'custom_data' => [],
                'user_agent' => 'Mozilla/5.0',
                'ip_address' => '127.0.0.1',
                'order_description' => 'Thanh toan don hang',
            ],
            'transaction' => [
                'id' => '384c66dd-41e6-4316-a544-b4141682595c',
                'payment_method' => 'BANK_TRANSFER',
                'transaction_id' => 'TXN-001',
                'transaction_type' => 'PAYMENT',
                'transaction_date' => '2026-03-09 12:00:00',
                'transaction_status' => 'APPROVED',
                'transaction_amount' => '100000',
                'transaction_currency' => 'VND',
            ],
            'customer' => [
                'id' => 'bae12d2f-0580-4669-8841-cc35cf671613',
                'customer_id' => 'guest-SP2001',
            ],
        ];
    }
}
