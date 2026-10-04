<?php

use App\Modules\Customers\Http\Controllers\AccountController;
use App\Modules\Customers\Http\Controllers\AddressController;
use App\Modules\Customers\Http\Controllers\AuthController;
use App\Modules\Customers\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Route;

// Storefront customer area. (Staff sign in to the admin app separately; both are rows in `users`.)
Route::prefix('account')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.attempt');
        Route::get('register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:register')->name('register.store');

        Route::get('forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
        Route::post('forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:password-email')->name('password.email');
        Route::get('reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
        Route::post('reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:password-update')->name('password.update');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/', [AccountController::class, 'show'])->name('account');
        Route::get('profile', [AccountController::class, 'profile'])->name('account.profile');
        Route::put('profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
        Route::put('password', [AccountController::class, 'updatePassword'])->name('account.password.update');

        Route::get('addresses', [AddressController::class, 'index'])->name('account.addresses');
        Route::get('addresses/create', [AddressController::class, 'create'])->name('account.addresses.create');
        Route::post('addresses', [AddressController::class, 'store'])->name('account.addresses.store');
        Route::get('addresses/{address}/edit', [AddressController::class, 'edit'])->whereNumber('address')->name('account.addresses.edit');
        Route::put('addresses/{address}', [AddressController::class, 'update'])->whereNumber('address')->name('account.addresses.update');
        Route::delete('addresses/{address}', [AddressController::class, 'destroy'])->whereNumber('address')->name('account.addresses.destroy');
        Route::post('addresses/{address}/default', [AddressController::class, 'makeDefault'])->whereNumber('address')->name('account.addresses.default');
    });
});
