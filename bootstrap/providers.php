<?php

use App\Providers\Filament\AdminPanelProvider;
use App\Providers\HorizonServiceProvider;
use Core\Providers\AppServiceProvider;
use Core\Providers\CommandServiceProvider;
use Core\Providers\DomainRegistrationRepository;

return [
    AdminPanelProvider::class,
    HorizonServiceProvider::class,
    AppServiceProvider::class,
    CommandServiceProvider::class,
    DomainRegistrationRepository::class,
];
