<?php

use App\Providers\AdminAccessServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\HorizonServiceProvider;
use Core\Providers\AppServiceProvider;
use Core\Providers\CommandServiceProvider;
use Core\Providers\DomainRegistrationRepository;

return [
    AdminAccessServiceProvider::class,
    AdminPanelProvider::class,
    HorizonServiceProvider::class,
    AppServiceProvider::class,
    CommandServiceProvider::class,
    DomainRegistrationRepository::class,
];
