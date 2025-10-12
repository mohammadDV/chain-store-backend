<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketMessageResource\Pages;
use Domain\Ticket\Models\TicketMessage;
use Domain\Ticket\Models\Ticket;
use Domain\User\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketMessageResource extends Resource
{
    protected static ?string $model = TicketMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left';

    protected static ?string $navigationGroup = 'Support';

    protected static ?int $navigationSort = 23;

    public static function getNavigationLabel(): string
    {
        return __('site.ticket_messages');
    }

    public static function getModelLabel(): string
    {
        return __('site.ticket_message');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.ticket_messages');
    }

    public static function getNavigationGroup(): string
    {
        return __('site.Ticket Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('site.ticket_message_information'))
                    ->schema([
                        Forms\Components\Select::make('ticket_id')
                            ->label(__('site.ticket_message_ticket'))
                            ->options(Ticket::with('subject')->get()->pluck('subject.title', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label(__('site.ticket_message_user'))
                            ->options(User::all()->mapWithKeys(function ($user) {
                                return [$user->id => $user->getFilamentName()];
                            }))
                            ->searchable()
                            ->required(),
                        Forms\Components\Textarea::make('message')
                            ->label(__('site.ticket_message_content'))
                            ->required()
                            ->rows(4),
                        Forms\Components\FileUpload::make('file')
                            ->label(__('site.ticket_message_attachment'))
                            ->directory('ticket-messages')
                            ->acceptedFileTypes(['image/*', 'application/pdf', 'text/*'])
                            ->maxSize(5120), // 5MB
                        Forms\Components\Select::make('status')
                            ->label(__('site.ticket_message_status'))
                            ->options([
                                'pending' => __('site.pending'),
                                'read' => __('site.read'),
                            ])
                            ->default('pending')
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
                TextColumn::make('ticket.subject.title')
                    ->label(__('site.ticket_subject'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label(__('site.ticket_message_user'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('message')
                    ->label(__('site.ticket_message_content'))
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('file')
                    ->label(__('site.ticket_message_attachment'))
                    ->formatStateUsing(fn ($state) => $state ? __('site.message_has_attachment') : __('site.message_no_attachment')),
                TextColumn::make('status')
                    ->label(__('site.ticket_message_status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'read' => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => __('site.pending'),
                        'read' => __('site.read'),
                    }),
                TextColumn::make('created_at')
                    ->label(__('site.ticket_message_created_at'))
                    ->dateTime('Y/m/d H:i:s')
                    ->size(TextColumnSize::Small)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => __('site.pending'),
                        'read' => __('site.read'),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTicketMessages::route('/'),
            'create' => Pages\CreateTicketMessage::route('/create'),
            'edit' => Pages\EditTicketMessage::route('/{record}/edit'),
            'view' => Pages\ViewTicketMessage::route('/{record}'),
        ];
    }
}
