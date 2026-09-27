<?php

use Domain\Product\Models\Order;
use Domain\Product\Services\RevenueReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('aggregates order counts sales and profit for a date range', function () {
    Order::factory()->create([
        'status' => Order::PENDING,
        'total_amount' => 10_000,
        'profit' => 1_000,
        'created_at' => '2026-09-10 10:00:00',
    ]);
    Order::factory()->create([
        'status' => Order::PAID,
        'total_amount' => 100_000,
        'profit' => 40_000,
        'created_at' => '2026-09-12 10:00:00',
    ]);
    Order::factory()->create([
        'status' => Order::DELIVERED,
        'total_amount' => 200_000,
        'profit' => 80_000,
        'created_at' => '2026-09-15 10:00:00',
    ]);
    Order::factory()->create([
        'status' => Order::REFUNDED,
        'total_amount' => 50_000,
        'profit' => 20_000,
        'created_at' => '2026-09-18 10:00:00',
    ]);
    Order::factory()->create([
        'status' => Order::SHIPPED,
        'total_amount' => 999_000,
        'profit' => 400_000,
        'created_at' => '2026-08-01 10:00:00',
    ]);

    $report = app(RevenueReportService::class)->report(
        Carbon::parse('2026-09-01')->startOfDay(),
        Carbon::parse('2026-09-30')->endOfDay(),
    );

    expect($report->ordersCount)->toBe(4)
        ->and($report->completedCount)->toBe(2)
        ->and($report->refundedCount)->toBe(1)
        ->and($report->totalSales)->toBe(300_000.0)
        ->and($report->totalProfit)->toBe(120_000.0);
});

it('excludes pending cancelled failed and expired orders from profit', function () {
    foreach ([Order::PENDING, Order::CANCELLED, Order::FAILED, Order::EXPIRED, Order::RETURNED] as $status) {
        Order::factory()->create([
            'status' => $status,
            'total_amount' => 100_000,
            'profit' => 50_000,
            'created_at' => '2026-09-10 10:00:00',
        ]);
    }

    $report = app(RevenueReportService::class)->report(
        Carbon::parse('2026-09-01'),
        Carbon::parse('2026-09-30'),
    );

    expect($report->ordersCount)->toBe(5)
        ->and($report->completedCount)->toBe(0)
        ->and($report->refundedCount)->toBe(0)
        ->and($report->totalSales)->toBe(0.0)
        ->and($report->totalProfit)->toBe(0.0);
});

it('returns zeros when there are no orders', function () {
    $report = app(RevenueReportService::class)->report();

    expect($report->ordersCount)->toBe(0)
        ->and($report->completedCount)->toBe(0)
        ->and($report->refundedCount)->toBe(0)
        ->and($report->totalSales)->toBe(0.0)
        ->and($report->totalProfit)->toBe(0.0);
});

it('aggregates with a single database query', function () {
    Order::factory()->count(3)->create([
        'status' => Order::PAID,
        'total_amount' => 10_000,
        'profit' => 4_000,
        'created_at' => '2026-09-10 10:00:00',
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    app(RevenueReportService::class)->report(
        Carbon::parse('2026-09-01'),
        Carbon::parse('2026-09-30'),
    );

    $orderQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'from "orders"')
            || str_contains(strtolower($query['query']), 'from `orders`')
            || str_contains(strtolower($query['query']), 'from orders'));

    expect($orderQueries)->toHaveCount(1)
        ->and(strtolower($orderQueries->first()['query']))->toContain('count(*)')
        ->and(strtolower($orderQueries->first()['query']))->toContain('case when');
});
