<?php

namespace Core\Providers;

use Domain\User\Listeners\SendRegistrationTelegramNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        App::setlocale('fa');

        Event::listen(Registered::class, SendRegistrationTelegramNotification::class);
    }
}
