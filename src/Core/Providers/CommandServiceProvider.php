<?php

namespace Core\Providers;

use Core\Console\Commands\AddPermissions;
use Core\Console\Commands\ProductCommand;
use Core\Console\Commands\ProductListCommand;
use Core\Console\Commands\ProductSizeCommand;
use Core\Console\Commands\UpdateStockCommand;
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
            ProductListCommand::class,
            ProductCommand::class,
            UpdateStockCommand::class,
            ProductSizeCommand::class,
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
