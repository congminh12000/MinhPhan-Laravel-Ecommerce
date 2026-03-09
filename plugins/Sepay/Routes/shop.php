<?php

use Illuminate\Support\Facades\Route;
use Plugin\Sepay\Controllers\SepayController;

Route::name('sepay.')
    ->group(function () {
        Route::get('/sepay/return/success', [SepayController::class, 'success'])->name('return.success');
        Route::get('/sepay/return/error', [SepayController::class, 'error'])->name('return.error');
        Route::get('/sepay/return/cancel', [SepayController::class, 'cancel'])->name('return.cancel');
        Route::post('/callback/sepay', [SepayController::class, 'callback'])->name('callback');
        Route::post('/sepay/ipn', [SepayController::class, 'callback'])->name('ipn');
        Route::post('/api/sepay/webhook', [SepayController::class, 'callback'])->name('webhook');
    });
