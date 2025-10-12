<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ReviewResource;
use App\Filament\Resources\TicketResource;
use App\Filament\Resources\UserResource;
use Carbon\Carbon;
use Domain\Review\Models\Review;
use Domain\Ticket\Models\Ticket;
use Domain\User\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {

        return [];
        // Count projects and claims from last 30 days
        // $businesses = Business::where('status', Business::PENDING)->count();
        // $users = User::where('level', '!=', 3)->where('status', 1)->count();
        // $reviews = Review::where('status', Review::PENDING)->count();


        // return [
        //     Stat::make(__('site.businesses'), $businesses)
        //         ->description(__('site.businesses_pending'))
        //         ->descriptionIcon('heroicon-m-rectangle-stack')
        //         ->color('primary')
        //         ->chart([7, 2, 10, 3, 15, 4, 17]) // Sample chart data
        //         ->url(BusinessResource::getUrl('index')),

        //     Stat::make(__('site.users'), $users)
        //         ->description(__('site.active_users'))
        //         ->descriptionIcon('heroicon-m-document-text')
        //         ->color('success')
        //         ->chart([3, 8, 5, 12, 6, 9, 4]) // Sample chart data
        //         ->url(UserResource::getUrl('index')),

        //     Stat::make(__('site.tickets'), Ticket::where('status', 'active')->count())
        //         ->description(__('site.tickets_need_attention'))
        //         ->descriptionIcon('heroicon-m-clock')
        //         ->chart([3, 8, 5, 12, 6, 9, 4]) // Sample chart data
        //         ->url(TicketResource::getUrl('index'))
        //         ->color('warning'),

        //     Stat::make(__('site.reviews'), $reviews)
        //         ->description(__('site.pending_reviews'))
        //         ->descriptionIcon('heroicon-m-clock')
        //         ->url(ReviewResource::getUrl('index', ['tableFilters[status][value]' => Review::PENDING]))
        //         ->chart([3, 8, 5, 12, 6, 9, 4]) // Sample chart data
        //         ->color('danger'),
        // ];
    }
}
