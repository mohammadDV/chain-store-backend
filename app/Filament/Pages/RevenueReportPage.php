<?php

namespace App\Filament\Pages;

use Domain\AdminAccess\AdminPermission;
use Domain\Product\Data\RevenueReport;
use Domain\Product\Services\RevenueReportService;
use Domain\User\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class RevenueReportPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'revenue';

    /**
     * @var array{from: ?string, until: ?string}
     */
    public array $data = [
        'from' => null,
        'until' => null,
    ];

    /**
     * @var array{
     *     orders_count: int,
     *     completed_count: int,
     *     refunded_count: int,
     *     total_sales: float,
     *     total_profit: float
     * }|null
     */
    public ?array $report = null;

    public static function getNavigationGroup(): ?string
    {
        return __('site.Payment Management');
    }

    public static function getNavigationLabel(): string
    {
        return __('site.revenue_report');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->can(AdminPermission::REVENUE_VIEW);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getTitle(): string
    {
        return __('site.revenue_report');
    }

    public function mount(RevenueReportService $revenueReportService): void
    {
        abort_unless(static::canAccess(), 403);

        $this->data = [
            'from' => now()->startOfMonth()->toDateString(),
            'until' => now()->toDateString(),
        ];

        $this->loadReport($revenueReportService);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('site.date_range'))
                    ->schema([
                        DatePicker::make('from')
                            ->label(__('site.created_from'))
                            ->native(false)
                            ->required(),
                        DatePicker::make('until')
                            ->label(__('site.created_until'))
                            ->native(false)
                            ->required(),
                    ])
                    ->columns(2)
                    ->footerActions([
                        Action::make('calculate')
                            ->label(__('site.calculate_revenue'))
                            ->action('applyFilters'),
                    ]),
                Section::make()
                    ->schema(fn (): array => $this->stats())
                    ->columns([
                        'default' => 1,
                        'sm' => 2,
                        'xl' => 3,
                    ])
                    ->contained(false)
                    ->gridContainer(),
            ])
            ->statePath('data');
    }

    public function applyFilters(RevenueReportService $revenueReportService): void
    {
        $this->loadReport($revenueReportService);
    }

    public function formatAmount(float $amount): string
    {
        return number_format($amount, 0).' '.__('site.currency');
    }

    /**
     * @return list<Stat>
     */
    private function stats(): array
    {
        $report = $this->report ?? [
            'orders_count' => 0,
            'completed_count' => 0,
            'refunded_count' => 0,
            'total_sales' => 0.0,
            'total_profit' => 0.0,
        ];

        return [
            Stat::make(__('site.total_profit'), $this->formatAmount((float) $report['total_profit']))
                ->color('success'),
            Stat::make(__('site.total_sales'), $this->formatAmount((float) $report['total_sales']))
                ->color('gray'),
            Stat::make(__('site.orders_count'), number_format((int) $report['orders_count']))
                ->color('gray'),
            Stat::make(__('site.completed_orders_count'), number_format((int) $report['completed_count']))
                ->color('primary'),
            Stat::make(__('site.refunded_orders_count'), number_format((int) $report['refunded_count']))
                ->color('danger'),
        ];
    }

    private function loadReport(RevenueReportService $revenueReportService): void
    {
        $from = filled($this->data['from'] ?? null)
            ? Carbon::parse($this->data['from'])->startOfDay()
            : null;
        $until = filled($this->data['until'] ?? null)
            ? Carbon::parse($this->data['until'])->endOfDay()
            : null;

        $this->report = $this->toArray($revenueReportService->report($from, $until));
    }

    /**
     * @return array{
     *     orders_count: int,
     *     completed_count: int,
     *     refunded_count: int,
     *     total_sales: float,
     *     total_profit: float
     * }
     */
    private function toArray(RevenueReport $report): array
    {
        return [
            'orders_count' => $report->ordersCount,
            'completed_count' => $report->completedCount,
            'refunded_count' => $report->refundedCount,
            'total_sales' => $report->totalSales,
            'total_profit' => $report->totalProfit,
        ];
    }
}
