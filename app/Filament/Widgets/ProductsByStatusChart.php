<?php

namespace App\Filament\Widgets;

use Domain\Product\Models\Product;
use Filament\Widgets\ChartWidget;

class ProductByStatusChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = null;

    protected function getData(): array
    {
        $pendingBusinesses = Product::where('status', Product::PENDING)->count();
        $approvedBusinesses = Product::where('status', Product::COMPLETED)->count();
        $inProgressBusinesses = Product::where('status', Product::REJECT)->count();

        return [
            'datasets' => [
                [
                    'label' => __('site.businesses'),
                    'data' => [
                        $pendingBusinesses,
                        $approvedBusinesses,
                        $inProgressBusinesses,
                    ],
                    'backgroundColor' => [
                        '#f59e0b', // Orange for pending
                        '#10b981', // Green for approved
                        '#ef4444', // Red for rejected
                    ],
                ],
            ],
            'labels' => [
                __('site.pending'),
                __('site.approved'),
                __('site.rejected')
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public function getHeading(): string
    {
        return __('site.businesses_by_status');
    }
}