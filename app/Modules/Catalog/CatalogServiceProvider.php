<?php

namespace App\Modules\Catalog;

use App\Modules\Catalog\Models\Category;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class CatalogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Route::middleware(['api', 'auth:sanctum', 'admin'])
            ->prefix('api/v1/admin')
            ->name('admin.')
            ->group(__DIR__.'/routes.php');

        Route::middleware('web')->group(__DIR__.'/storefront.php');

        // Navigation categories for the storefront header/drawer/footer.
        View::composer('storefront.partials.*', function ($view) {
            static $nav;

            $nav ??= Category::active()->roots()
                ->with('children', fn ($q) => $q->active())
                ->orderBy('sort_order')->orderBy('name')
                ->get();

            $view->with('navCategories', $nav);
        });
    }
}
