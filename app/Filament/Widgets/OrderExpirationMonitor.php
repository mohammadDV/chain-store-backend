<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Domain\Product\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class OrderExpirationMonitor extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $oneHourAgo = now()->subHour();

        // Count pending orders that should be expired
        $pendingToExpire = Order::query()
            ->where('status', Order::PENDING)
            ->where('active', 1)
            ->where('created_at', '<=', $oneHourAgo)
            ->count();

        // Count total pending orders
        $totalPending = Order::query()
            ->where('status', Order::PENDING)
            ->where('active', 1)
            ->count();

        // Count expired orders today
        $expiredToday = Order::query()
            ->where('status', Order::EXPIRED)
            ->whereDate('updated_at', today())
            ->count();

        // Count expired orders in last 24 hours
        $expiredLast24h = Order::query()
            ->where('status', Order::EXPIRED)
            ->where('updated_at', '>=', now()->subDay())
            ->count();

        return [
            Stat::make(__('site.Pending Orders (Should Expire)'), $pendingToExpire)
                ->description(__('site.Orders pending > 1 hour'))
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingToExpire > 0 ? 'warning' : 'success')
                ->chart($this->getPendingChartData()),

            Stat::make(__('site.Total Pending Orders'), $totalPending)
                ->description(__('site.All active pending orders'))
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info'),

            Stat::make(__('site.Expired Today'), $expiredToday)
                ->description(__('site.Expired in last 24 hours') . ': ' . $expiredLast24h)
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->chart($this->getExpiredChartData()),
        ];
    }

    /**
     * Get chart data for pending orders (last 7 days)
     */
    protected function getPendingChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = Order::query()
                ->where('status', Order::PENDING)
                ->where('active', 1)
                ->whereDate('created_at', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }

    /**
     * Get chart data for expired orders (last 7 days)
     */
    protected function getExpiredChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = Order::query()
                ->where('status', Order::EXPIRED)
                ->whereDate('updated_at', $date)
                ->count();
            $data[] = $count;
        }
        return $data;
    }
}
