<?php

namespace Core\Providers;

use Core\Console\Commands\AddPermissions;
use Core\Console\Commands\Product;
use Core\Console\Commands\ProductList;
use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->commands([
            AddPermissions::class,
            ProductList::class,
            Product::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
