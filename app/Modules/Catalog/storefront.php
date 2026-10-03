<?php

use App\Modules\Catalog\Http\Controllers\Storefront\HomeController;
use App\Modules\Catalog\Http\Controllers\Storefront\ListingController;
use Illuminate\Support\Facades\Route;

// Public storefront routes (server-rendered Blade).
Route::get('/', HomeController::class)->name('home');
Route::get('/shop', [ListingController::class, 'shop'])->name('shop');
Route::get('/category/{slug}', [ListingController::class, 'category'])
    ->where('slug', '[a-z0-9\-_]+')->name('category.show');
