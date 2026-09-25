<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use App\Filament\Resources\TicketSubjectResource\Pages\ListTicketSubjects;
use App\Filament\Resources\TicketSubjectResource\Pages\CreateTicketSubject;
use App\Filament\Resources\TicketSubjectResource\Pages\EditTicketSubject;
use App\Filament\Resources\TicketSubjectResource\Pages\ViewTicketSubject;
use App\Filament\Resources\TicketSubjectResource\Pages;
use Domain\Ticket\Models\TicketSubject;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TicketSubjectResource extends Resource
{
    protected static ?string $model = TicketSubject::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';

    protected static string | \UnitEnum | null $navigationGroup = 'Support';

    protected static ?int $navigationSort = 24;

    public static function getNavigationLabel(): string
    {
        return __('site.ticket_subjects');
    }

    public static function getModelLabel(): string
    {
        return __('site.ticket_subject');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.ticket_subjects');
    }

    public static function getNavigationGroup(): string
    {
        return __('site.Ticket Management');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('site.ticket_subject_information'))
                    ->schema([
                        TextInput::make('title')
                            ->label(__('site.ticket_subject_title'))
                            ->required()
                            ->maxLength(255),
                        Hidden::make('user_id')
                            ->default(Auth::id()),
                        Select::make('status')
                            ->label(__('site.ticket_subject_status'))
                            ->options([
                                0 => __('site.inactive'),
                                1 => __('site.active'),
                            ])
                            ->default(0)
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
                TextColumn::make('title')
                    ->label(__('site.ticket_subject_title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label(__('site.ticket_subject_user'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('site.ticket_subject_status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '0' => 'danger',
                        '1' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '0' => __('site.inactive'),
                        '1' => __('site.active'),
                        default => $state,
                    }),
                TextColumn::make('created_at')
                    ->label(__('site.created_at'))
                    ->dateTime('Y/m/d H:i:s')
                    ->size(TextSize::Small)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        0 => __('site.inactive'),
                        1 => __('site.active'),
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
            'index' => ListTicketSubjects::route('/'),
            'create' => CreateTicketSubject::route('/create'),
            'edit' => EditTicketSubject::route('/{record}/edit'),
            'view' => ViewTicketSubject::route('/{record}'),
        ];
    }
}
