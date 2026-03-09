<?php

namespace Plugin\Sepay\Controllers;

use Beike\Models\Order;
use Beike\Repositories\OrderPaymentRepo;
use Beike\Repositories\OrderRepo;
use Beike\Services\StateMachineService;
use Beike\Shop\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Plugin\Sepay\Services\SepayService;

class SepayController extends Controller
{
    public function success(Request $request)
    {
        return $this->renderReturnPage($request, 'success');
    }

    public function error(Request $request)
    {
        return $this->renderReturnPage($request, 'error');
    }

    public function cancel(Request $request)
    {
        return $this->renderReturnPage($request, 'cancel');
    }

    public function callback(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $context = $this->buildCallbackContext($request, $payload);
        Log::info('SePay callback received', $context);

        if ($request->header('X-Secret-Key') !== (string) plugin_setting('sepay.secret_key')) {
            Log::warning('SePay callback rejected because of invalid secret key', $context);

            return json_fail(trans('Sepay::common.invalid_secret_key'), [], 403);
        }

        $orderNumber = (string) data_get($payload, 'order.order_invoice_number', '');
        $order = OrderRepo::getOrderByNumber($orderNumber);

        if (! $order) {
            Log::warning('SePay callback received for unknown order', $context);

            return json_success(trans('Sepay::common.order_not_found'));
        }

        $transactionId = (string) data_get($payload, 'transaction.transaction_id', '');
        $result = [
            'processed'          => false,
            'notification_type'  => data_get($payload, 'notification_type'),
            'order_status'       => data_get($payload, 'order.order_status'),
            'transaction_status' => data_get($payload, 'transaction.transaction_status'),
        ];

        OrderPaymentRepo::createOrUpdatePayment($order->id, [
            'transaction_id' => $transactionId,
            'callback'       => $payload,
            'response'       => $result,
        ]);

        if ($order->status !== StateMachineService::UNPAID) {
            Log::info('SePay callback acknowledged for already processed order', [
                ...$context,
                'order_status_before' => $order->status,
            ]);

            return json_success(trans('Sepay::common.callback_acknowledged'));
        }

        if (! $this->shouldMarkAsPaid($payload)) {
            Log::info('SePay callback did not qualify for paid transition', [
                ...$context,
                'order_status_before' => $order->status,
            ]);

            return json_success(trans('Sepay::common.callback_acknowledged'));
        }

        StateMachineService::getInstance($order)->changeStatus(StateMachineService::PAID);
        $order->refresh();

        OrderPaymentRepo::createOrUpdatePayment($order->id, [
            'transaction_id' => $transactionId,
            'callback'       => $payload,
            'response'       => array_merge($result, ['processed' => true]),
        ]);

        Log::info('SePay callback marked order as paid', [
            ...$context,
            'order_status_after' => $order->status,
        ]);

        return json_success(trans('Sepay::common.callback_processed'));
    }

    private function renderReturnPage(Request $request, string $type)
    {
        $order = $this->findOrder($request);
        $retryUrl = $order ? shop_route('orders.pay', $order->number) : null;

        if ($order) {
            $returnData = SepayService::getReturnData($type);
        } else {
            $returnData = [
                'title'   => trans('Sepay::common.return_missing_order_title'),
                'message' => trans('Sepay::common.return_missing_order_message'),
                'alert'   => 'danger',
            ];
        }

        $data = hook_filter('account.order.show.data', [
            'order'     => $order,
            'html_items'=> [],
            'retry_url' => $retryUrl,
            'type'      => $type,
            'title'     => $returnData['title'],
            'message'   => $returnData['message'],
            'alert'     => $returnData['alert'],
        ]);

        return view('Sepay::checkout.return', $data);
    }

    private function findOrder(Request $request): ?Order
    {
        $orderNumber = (string) $request->query('order_number', '');
        if (! $orderNumber) {
            return null;
        }

        $customer = current_customer();
        if ($customer) {
            $order = OrderRepo::getOrderByNumber($orderNumber, $customer);
            if ($order) {
                return $order;
            }
        }

        $email = (string) $request->query('email', '');
        if (! $email) {
            return null;
        }

        return Order::query()
            ->with(['orderProducts', 'orderTotals', 'orderHistories', 'orderShipments'])
            ->where('number', $orderNumber)
            ->where('email', $email)
            ->first();
    }

    private function shouldMarkAsPaid(array $payload): bool
    {
        return data_get($payload, 'notification_type') === 'ORDER_PAID'
            && data_get($payload, 'order.order_status') === 'CAPTURED'
            && data_get($payload, 'transaction.transaction_status') === 'APPROVED';
    }

    private function buildCallbackContext(Request $request, array $payload): array
    {
        return [
            'callback_path'       => '/' . ltrim($request->path(), '/'),
            'content_type'        => (string) $request->header('Content-Type'),
            'has_secret_key'      => filled($request->header('X-Secret-Key')),
            'notification_type'   => data_get($payload, 'notification_type'),
            'order_number'        => (string) data_get($payload, 'order.order_invoice_number', ''),
            'order_status'        => (string) data_get($payload, 'order.order_status', ''),
            'transaction_id'      => (string) data_get($payload, 'transaction.transaction_id', ''),
            'transaction_status'  => (string) data_get($payload, 'transaction.transaction_status', ''),
            'environment'         => (string) plugin_setting('sepay.environment'),
        ];
    }
}
