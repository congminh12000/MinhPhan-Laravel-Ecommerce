<?php

namespace Plugin\Sepay;

use Illuminate\Support\Collection;

class Bootstrap
{
    public function boot(): void
    {
        add_hook_filter('repo.plugin.payment_methods', function ($paymentMethods) {
            if (is_vnd_currency(current_currency_code())) {
                return $paymentMethods;
            }

            if ($paymentMethods instanceof Collection) {
                return $paymentMethods->reject(function ($item) {
                    return $item->code === 'sepay';
                });
            }

            return $paymentMethods;
        });
    }
}
