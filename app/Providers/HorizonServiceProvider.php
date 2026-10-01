<?php

namespace App\Providers;

use Domain\AdminAccess\Services\AdminAccessService;
use Domain\User\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Require authentication in every environment (including local).
     */
    protected function authorization(): void
    {
        $this->gate();

        Horizon::auth(function ($request) {
            return Gate::check('viewHorizon', [$request->user()]);
        });
    }

    /**
     * Only the configured super-admin email(s) may open Horizon.
     * Default: admin@gmail.com via ADMIN_SUPER_ADMIN_EMAILS.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            if (! $user instanceof User) {
                return false;
            }

            return app(AdminAccessService::class)->isSuperAdmin($user);
        });
    }
}
