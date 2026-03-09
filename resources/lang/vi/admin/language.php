<?php

return array_replace_recursive(require resource_path('lang/en/admin/language.php'), [
    'languages_index' => 'Danh sách',
    'languages_create' => 'Tạo mới',
    'languages_show' => 'Chi tiết',
    'languages_update' => 'Cập nhật',
    'languages_delete' => 'Xóa',
    'error_default_language_cannot_delete' => 'Không thể xóa ngôn ngữ mặc định!',
    'help_install' => 'Lưu ý: sau khi cài một ngôn ngữ mới, bạn cần cấu hình lại sản phẩm, danh mục, menu, mô-đun trang chủ, footer và các nội dung liên quan để giao diện hiển thị đúng.',
]);
