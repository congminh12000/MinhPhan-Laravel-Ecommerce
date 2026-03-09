<?php
/**
 * SettingsSeeder.php
 *
 * @copyright  2022 beikeshop.com - All Rights Reserved
 * @link       https://beikeshop.com
 * @author     guangda <service@guangda.work>
 * @created    2022-09-05 19:42:42
 * @modified   2022-09-05 19:42:42
 */

namespace Database\Seeders;

use Beike\Models\Brand;
use Beike\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = $this->getItems();

        if ($items) {
            Setting::query()->truncate();
            foreach ($items as $item) {
                Setting::query()->create($item);
            }
        }
    }


    public function getItems()
    {
        return [
            ["type" => "system", "space" => "base", "name" => "country_id", "value" => "230", "json" => 0],
            ["type" => "system", "space" => "base", "name" => "locale", "value" => "vi", "json" => 0],
            ["type" => "system", "space" => "base", "name" => "theme", "value" => "default", "json" => 0],
            ["type" => "plugin", "space" => "service_charge", "name" => "status", "value" => "1", "json" => 0],
            ["type" => "system", "space" => "base", "name" => "status", "value" => "", "json" => 0],
            ["type" => "system", "space" => "base", "name" => "admin_name", "value" => "admin", "json" => 0],
            ["type" => "system", "space" => "base", "name" => "tax", "value" => "1", "json" => 0],
            ["type" => "system", "space" => "base", "name" => "tax_address", "value" => "payment", "json" => 0],
            ["type" => "system", "space" => "base", "name" => "currency", "value" => "VNĐ", "json" => 0],
            ["type" => "system", "space" => "base", "name" => "zone_id", "value" => "3780", "json" => 0],
            ["type" => "plugin", "space" => "header_menu", "name" => "status", "value" => "1", "json" => 0],
            ["type" => "plugin", "space" => "stripe", "name" => "publishable_key", "value" => "", "json" => 0],
            ["type" => "plugin", "space" => "stripe", "name" => "secret_key", "value" => "", "json" => 0],
            ["type" => "plugin", "space" => "stripe", "name" => "test_mode", "value" => "1", "json" => 0],
            ["type" => "plugin", "space" => "paypal", "name" => "sandbox_client_id", "value" => "", "json" => 0],
            ["type" => "plugin", "space" => "paypal", "name" => "sandbox_secret", "value" => "", "json" => 0],
            ["type" => "plugin", "space" => "paypal", "name" => "live_client_id", "value" => "", "json" => 0],
            ["type" => "plugin", "space" => "paypal", "name" => "live_secret", "value" => "", "json" => 0],
            ["type" => "plugin", "space" => "paypal", "name" => "sandbox_mode", "value" => "1", "json" => 0],
            ["type" => "system", "space" => "base", "name" => "logo", "value" => "catalog/logo.png", "json" => 0],
            ["type" => "system", "space" => "base", "name" => "placeholder", "value" => "catalog/placeholder.png", "json" => 0],
            ["type" => "system", "space" => "base", "name" => "favicon", "value" => "catalog/favicon.png", "json" => 0],
            ["type" => "system", "space" => "base", "name" => "meta_title", "value" => "Nền tảng thương mại điện tử mã nguồn mở dễ dùng cho bán hàng xuyên biên giới", "json" => 0],
            ["type" => "system", "space" => "base", "name" => "meta_description", "value" => "BeikeShop là nền tảng xây dựng website thương mại điện tử mã nguồn mở phát triển trên Laravel. Hệ thống hỗ trợ quản lý sản phẩm, đơn hàng, khách hàng, thanh toán, vận chuyển và vận hành cửa hàng trên một giao diện thống nhất.", "json" => 0],
            ["type" => "system", "space" => "base", "name" => "meta_keywords", "value" => "thương mại điện tử mã nguồn mở, website bán hàng, ecommerce Laravel, bán hàng xuyên biên giới, nền tảng storefront", "json" => 0],
            ["type" => "system", "space" => "base", "name" => "telephone", "value" => "028-xxxxxxxx", "json" => 0],
            ["type" => "system", "space" => "base", "name" => "email", "value" => "support@example.com", "json" => 0],
            ["type" => "plugin", "space" => "flat_shipping", "name" => "type", "value" => "percent", "json" => 0],
            ["type" => "plugin", "space" => "flat_shipping", "name" => "value", "value" => "10", "json" => 0],
            ["type" => "system", "space" => "base", "name" => "default_customer_group_id", "value" => "1", "json" => 0],
            ["type" => "plugin", "space" => "stripe", "name" => "status", "value" => "1", "json" => 0],
            ["type" => "plugin", "space" => "latest_products", "name" => "status", "value" => "1", "json" => 0],
            ["type" => "plugin", "space" => "flat_shipping", "name" => "status", "value" => "1", "json" => 0],
            ["type" => "plugin", "space" => "paypal", "name" => "status", "value" => "1", "json" => 0],
            ["type" => "system", "space" => "base", "name" => "rate_api_key", "value" => "", "json" => 0],
        ];
    }
}
