<?php

use App\Modules\Catalog\Http\Controllers\Admin\AttributeController;
use App\Modules\Catalog\Http\Controllers\Admin\CategoryController;
use App\Modules\Catalog\Http\Controllers\Admin\ProductController;
use App\Modules\Catalog\Http\Controllers\Admin\ProductImageController;
use App\Modules\Catalog\Http\Controllers\Admin\ProductVariantController;
use App\Modules\Catalog\Http\Controllers\Admin\StockController;
use Illuminate\Support\Facades\Route;

// Mounted by CatalogServiceProvider under /api/v1/admin with auth:sanctum + admin.
// Permissions are enforced inside each controller via HasMiddleware.

Route::apiResource('categories', CategoryController::class);

Route::apiResource('attributes', AttributeController::class)->except('show');

Route::apiResource('products', ProductController::class);

Route::scopeBindings()->group(function () {
    Route::post('products/{product}/variants', [ProductVariantController::class, 'store']);
    Route::put('products/{product}/variants/{variant}', [ProductVariantController::class, 'update']);
    Route::delete('products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy']);

    Route::post('products/{product}/images', [ProductImageController::class, 'store']);
    Route::put('products/{product}/images/order', [ProductImageController::class, 'reorder']);
    Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy']);
});

Route::get('variants/{variant}/stock-movements', [StockController::class, 'index']);
Route::post('variants/{variant}/stock-adjustments', [StockController::class, 'store']);
