<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use App\Filament\Resources\TicketMessageResource\Pages\ListTicketMessages;
use App\Filament\Resources\TicketMessageResource\Pages\CreateTicketMessage;
use App\Filament\Resources\TicketMessageResource\Pages\EditTicketMessage;
use App\Filament\Resources\TicketMessageResource\Pages\ViewTicketMessage;
use App\Filament\Resources\TicketMessageResource\Pages;
use Domain\Ticket\Models\Ticket;
use Domain\Ticket\Models\TicketMessage;
use Domain\User\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TicketMessageResource extends Resource
{
    protected static ?string $model = TicketMessage::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left';

    protected static string | \UnitEnum | null $navigationGroup = 'Support';

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('site.ticket_message_information'))
                    ->schema([
                        Select::make('ticket_id')
                            ->label(__('site.ticket_message_ticket'))
                            ->options(Ticket::with('subject')->get()->pluck('subject.title', 'id'))
                            ->searchable()
                            ->required(),
                        Select::make('user_id')
                            ->label(__('site.ticket_message_user'))
                            ->options(User::all()->mapWithKeys(function ($user) {
                                return [$user->id => $user->getFilamentName()];
                            }))
                            ->searchable()
                            ->required(),
                        Textarea::make('message')
                            ->label(__('site.ticket_message_content'))
                            ->required()
                            ->rows(4),
                        FileUpload::make('file')
                            ->label(__('site.ticket_message_attachment'))
                            ->directory('ticket-messages')
                            ->acceptedFileTypes(['image/*', 'application/pdf', 'text/*'])
                            ->maxSize(5120), // 5MB
                        Select::make('status')
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
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => __('site.pending'),
                        'read' => __('site.read'),
                        default => $state,
                    }),
                TextColumn::make('created_at')
                    ->label(__('site.ticket_message_created_at'))
                    ->dateTime('Y/m/d H:i:s')
                    ->size(TextSize::Small)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => __('site.pending'),
                        'read' => __('site.read'),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
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
            'index' => ListTicketMessages::route('/'),
            'create' => CreateTicketMessage::route('/create'),
            'edit' => EditTicketMessage::route('/{record}/edit'),
            'view' => ViewTicketMessage::route('/{record}'),
        ];
    }
}
