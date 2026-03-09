<?php

return array_replace_recursive(require resource_path('lang/en/shop/account/password.php'), [
    'index' => 'Đổi mật khẩu',
    'new_password' => 'Mật khẩu mới',
    'new_password_confirmation' => 'Xác nhận mật khẩu mới',
    'new_password_err' => 'Mật khẩu xác nhận chưa khớp',
    'old_password' => 'Mật khẩu hiện tại',
    'origin_password_fail' => 'Mật khẩu hiện tại không đúng',
    'password_edit_success' => 'Đổi mật khẩu thành công',
]);
