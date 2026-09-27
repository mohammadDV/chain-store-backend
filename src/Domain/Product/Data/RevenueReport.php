<?php

namespace Domain\Product\Data;

use Carbon\CarbonInterface;

readonly class RevenueReport
{
    public function __construct(
        public int $ordersCount,
        public int $completedCount,
        public int $refundedCount,
        public float $totalSales,
        public float $totalProfit,
        public ?CarbonInterface $from = null,
        public ?CarbonInterface $to = null,
    ) {}
}
