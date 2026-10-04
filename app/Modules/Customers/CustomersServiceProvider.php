<?php

namespace App\Modules\Customers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CustomersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Route::middleware('web')->group(__DIR__.'/routes.php');
    }
}
