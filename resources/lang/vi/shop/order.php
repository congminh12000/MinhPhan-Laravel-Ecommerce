<?php

return array_replace_recursive(require resource_path('lang/en/shop/order.php'), [
    'invalid_order' => 'Đơn hàng không hợp lệ',
    'confirm_order' => 'Khách hàng xác nhận đã nhận hàng',
    'cancel_order' => 'Khách hàng hủy đơn hàng',
    'order_already_paid' => 'Đơn hàng này đã được thanh toán',
]);
