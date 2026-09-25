<?php

use App\Filament\Widgets\DashboardOverview;
use App\Filament\Widgets\LatestTickets;
use App\Filament\Widgets\OrderExpirationMonitor;
use App\Filament\Widgets\ProductsByStatusChart;
use App\Filament\Widgets\ReviewsByStatusChart;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    $this->actingAsAdmin();
});

it('renders dashboard widgets', function (string $widget) {
    livewire($widget)->assertSuccessful();
})->with([
    'overview' => DashboardOverview::class,
    'products chart' => ProductsByStatusChart::class,
    'reviews chart' => ReviewsByStatusChart::class,
    'latest tickets' => LatestTickets::class,
    'order expiration' => OrderExpirationMonitor::class,
]);
