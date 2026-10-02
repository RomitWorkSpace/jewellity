<?php

use App\Http\Controllers\Api\V1\Admin\AuthController as AdminAuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/ping', fn () => response()->json(['status' => 'ok']));

    // ---------------------------------------------------------------- Admin API
    // SPA cookie auth: call GET /sanctum/csrf-cookie first, then POST login.
    Route::prefix('admin')->group(function () {
        Route::post('auth/login', [AdminAuthController::class, 'login']);

        // Everything below needs an authenticated staff member.
        Route::middleware(['auth:sanctum', 'admin'])->group(function () {
            Route::post('auth/logout', [AdminAuthController::class, 'logout']);
            Route::get('auth/me', [AdminAuthController::class, 'me']);

            // Guard each resource with permissions, never role names, e.g.
            // Route::apiResource('products', ...)->middleware('permission:products.view');
        });
    });
});
