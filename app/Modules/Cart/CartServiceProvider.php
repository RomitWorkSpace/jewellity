<?php

namespace App\Modules\Cart;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class CartServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Per-request: the cart reads the current session.
        $this->app->scoped(CartService::class, fn ($app) => new CartService($app['session.store']));
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/routes.php');

        View::composer(['storefront.partials.*', 'components.layouts.storefront'], function ($view) {
            $view->with('cartCount', app(CartService::class)->count());
        });
    }
}
