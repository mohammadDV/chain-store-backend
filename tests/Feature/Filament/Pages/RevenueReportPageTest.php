<?php

use App\Filament\Pages\RevenueReportPage;
use Domain\AdminAccess\AdminPermission;
use Domain\Product\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('allows admins with revenue view permission to open the page', function () {
    $this->actingAsAdminWithPermissions([AdminPermission::REVENUE_VIEW]);

    Order::factory()->create([
        'status' => Order::PAID,
        'total_amount' => 150_000,
        'profit' => 60_000,
        'created_at' => now(),
    ]);

    livewire(RevenueReportPage::class)
        ->assertSuccessful()
        ->assertSee(__('site.total_profit'))
        ->assertSet('report.orders_count', 1)
        ->assertSet('report.completed_count', 1)
        ->assertSet('report.total_profit', 60_000.0);
});

it('forbids admins without revenue view permission', function () {
    $this->actingAsAdminWithPermissions([AdminPermission::TRANSACTIONS_VIEW]);

    livewire(RevenueReportPage::class)
        ->assertForbidden();
});

it('recalculates report for the selected date range', function () {
    $this->actingAsAdminWithPermissions([AdminPermission::REVENUE_VIEW]);

    Order::factory()->create([
        'status' => Order::DELIVERED,
        'total_amount' => 100_000,
        'profit' => 40_000,
        'created_at' => '2026-09-10 12:00:00',
    ]);
    Order::factory()->create([
        'status' => Order::REFUNDED,
        'total_amount' => 80_000,
        'profit' => 30_000,
        'created_at' => '2026-08-10 12:00:00',
    ]);

    livewire(RevenueReportPage::class)
        ->fillForm([
            'from' => '2026-09-01',
            'until' => '2026-09-30',
        ])
        ->call('applyFilters')
        ->assertSet('report.orders_count', 1)
        ->assertSet('report.completed_count', 1)
        ->assertSet('report.refunded_count', 0)
        ->assertSet('report.total_profit', 40_000.0)
        ->assertSet('report.total_sales', 100_000.0);
});
