<?php

namespace Plugin\Sepay;

use Illuminate\Support\Collection;

class Bootstrap
{
    public function boot(): void
    {
        add_hook_filter('repo.plugin.payment_methods', function ($paymentMethods) {
            if (current_currency_code() === 'VND') {
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
