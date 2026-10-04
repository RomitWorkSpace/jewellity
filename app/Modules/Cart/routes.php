<?php

use App\Modules\Cart\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;

Route::get('/cart', [CartController::class, 'show'])->name('cart');
Route::get('/cart/mini', [CartController::class, 'mini'])->name('cart.mini');

Route::middleware('throttle:cart')->group(function () {
    Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
    Route::patch('/cart/items/{variant}', [CartController::class, 'update'])->whereNumber('variant')->name('cart.items.update');
    Route::delete('/cart/items/{variant}', [CartController::class, 'destroy'])->whereNumber('variant')->name('cart.items.destroy');
});
