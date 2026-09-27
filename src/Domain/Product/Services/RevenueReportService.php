<?php

namespace Domain\Product\Services;

use Carbon\CarbonInterface;
use Domain\Product\Data\RevenueReport;
use Domain\Product\Models\Order;
use Illuminate\Database\Eloquent\Builder;

class RevenueReportService
{
    /**
     * Order statuses that count as successfully completed (revenue-generating).
     *
     * @return list<string>
     */
    public static function completedStatuses(): array
    {
        return [
            Order::PAID,
            Order::SHIPPED,
            Order::DELIVERED,
        ];
    }

    public function report(?CarbonInterface $from = null, ?CarbonInterface $to = null): RevenueReport
    {
        $completed = self::completedStatuses();
        $placeholders = implode(',', array_fill(0, count($completed), '?'));

        $row = $this->baseQuery($from, $to)
            ->toBase()
            ->selectRaw(
                "
                COUNT(*) as orders_count,
                COALESCE(SUM(CASE WHEN status IN ({$placeholders}) THEN 1 ELSE 0 END), 0) as completed_count,
                COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as refunded_count,
                COALESCE(SUM(CASE WHEN status IN ({$placeholders}) THEN total_amount ELSE 0 END), 0) as total_sales,
                COALESCE(SUM(CASE WHEN status IN ({$placeholders}) THEN profit ELSE 0 END), 0) as total_profit
                ",
                [...$completed, Order::REFUNDED, ...$completed, ...$completed],
            )
            ->first();

        return new RevenueReport(
            ordersCount: (int) ($row->orders_count ?? 0),
            completedCount: (int) ($row->completed_count ?? 0),
            refundedCount: (int) ($row->refunded_count ?? 0),
            totalSales: (float) ($row->total_sales ?? 0),
            totalProfit: (float) ($row->total_profit ?? 0),
            from: $from,
            to: $to,
        );
    }

    /**
     * @return Builder<Order>
     */
    private function baseQuery(?CarbonInterface $from, ?CarbonInterface $to): Builder
    {
        // Range predicates (not whereDate) keep created_at sargable for the index.
        return Order::query()
            ->when(
                $from,
                fn (Builder $query) => $query->where(
                    'created_at',
                    '>=',
                    $from->format('Y-m-d 00:00:00'),
                ),
            )
            ->when(
                $to,
                fn (Builder $query) => $query->where(
                    'created_at',
                    '<=',
                    $to->format('Y-m-d 23:59:59'),
                ),
            );
    }
}
