<?php

namespace App\Modules\Catalog;

use Illuminate\Support\Facades\Route;
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
    }
}
