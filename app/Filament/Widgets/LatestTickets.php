<?php

namespace App\Filament\Widgets;

use Domain\Ticket\Models\Ticket;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;

class LatestTickets extends BaseWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'آخرین تیکت‌ها';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Ticket::query()
                    ->with(['user', 'subject'])
                    ->latest()
            )
            ->columns([
                TextColumn::make('id')
                    ->label(__('site.table_id'))
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label(__('site.ticket_user'))
                    ->searchable(),
                TextColumn::make('subject.title')
                    ->label(__('site.ticket_subject'))
                    ->limit(30),
                TextColumn::make('status')
                    ->label(__('site.ticket_status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'warning',
                        'closed' => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => __('site.active'),
                        'closed' => __('site.closed'),
                    }),
                TextColumn::make('created_at')
                    ->label(__('site.ticket_created_at'))
                    ->dateTime('Y/m/d H:i:s')
                    ->sortable(),
            ])
            ->actions([
                Action::make('view')
                    ->url(fn (Ticket $record): string => route('filament.admin.resources.tickets.view', $record))
                    ->icon('heroicon-m-eye')
                    ->label(__('site.view')),
            ])
            ->paginated(false);
    }
}