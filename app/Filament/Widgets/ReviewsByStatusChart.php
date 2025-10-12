<?php

namespace App\Filament\Widgets;

use Domain\Review\Models\Review;
use Filament\Widgets\ChartWidget;

class ReviewsByStatusChart extends ChartWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return __('site.reviews_by_status');
    }

    protected function getData(): array
    {
        $statuses = [
            Review::PENDING => __('site.pending'),
            Review::APPROVED => __('site.approved'),
            Review::CANCELLED => __('site.canceled'),
        ];

        $data = [];
        $labels = [];
        $colors = [
            '#8b5cf6', // warning - pending
            '#10b981', // success - approved
            '#ef4444', // danger - canceled
        ];

        foreach ($statuses as $status => $label) {
            $count = Review::where('status', $status)->count();
            if ($count > 0) {
                $data[] = $count;
                $labels[] = $label;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => __('site.reviews'),
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}