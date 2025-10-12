<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use Domain\Ticket\Models\Ticket;
use Domain\Ticket\Models\TicketSubject;
use Domain\User\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Filters\SelectFilter;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Support';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('site.tickets');
    }

    public static function getModelLabel(): string
    {
        return __('site.ticket');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.tickets');
    }

    public static function getNavigationGroup(): string
    {
        return __('site.Ticket Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('site.ticket_information'))
                    ->schema([
                        Select::make('user_id')
                            ->label(__('site.ticket_user'))
                            ->options(User::all()->mapWithKeys(function ($user) {
                                return [$user->id => $user->getFilamentName()];
                            }))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('subject_id')
                            ->label(__('site.ticket_subject'))
                            ->options(TicketSubject::all()->pluck('title', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label(__('site.ticket_status'))
                            ->options([
                                'active' => __('site.active'),
                                'closed' => __('site.closed'),
                            ])
                            ->default('active')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('site.id'))
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label(__('site.ticket_user'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subject.title')
                    ->label(__('site.ticket_subject'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('site.ticket_status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'closed' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => __('site.active'),
                        'closed' => __('site.closed'),
                    }),
                TextColumn::make('messages_count')
                    ->label(__('site.ticket_messages_count'))
                    ->counts('messages')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('site.ticket_created_at'))
                    ->dateTime('Y/m/d H:i:s')
                    ->size(TextColumnSize::Small)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => __('site.active'),
                        'closed' => __('site.closed'),
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('markAsActive')
                    ->label(__('site.mark_active'))
                    ->icon('heroicon-m-play')
                    ->color('success')
                    ->visible(fn (Ticket $record): bool => $record->status === 'closed')
                    ->action(function (Ticket $record): void {
                        $record->update(['status' => 'active']);
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('site.confirm_mark_active'))
                    ->modalDescription(__('site.confirm_mark_active_description')),
                Action::make('markAsClosed')
                    ->label(__('site.mark_closed'))
                    ->icon('heroicon-m-check')
                    ->color('danger')
                    ->visible(fn (Ticket $record): bool => $record->status === 'active')
                    ->action(function (Ticket $record): void {
                        $record->update(['status' => 'closed']);
                    })
                    ->requiresConfirmation()
                    ->modalHeading(__('site.confirm_mark_closed'))
                    ->modalDescription(__('site.confirm_mark_closed_description')),
                ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
            'view' => Pages\ViewTicket::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Admins cannot create tickets
    }
}
