<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketSubjectResource\Pages;
use Domain\Ticket\Models\TicketSubject;
use Domain\User\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TicketSubjectResource extends Resource
{
    protected static ?string $model = TicketSubject::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Support';

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('site.ticket_subject_information'))
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('site.ticket_subject_title'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Hidden::make('user_id')
                            ->default(Auth::id()),
                        Forms\Components\Select::make('status')
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
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '0' => __('site.inactive'),
                        '1' => __('site.active'),
                    }),
                TextColumn::make('created_at')
                    ->label(__('site.created_at'))
                    ->dateTime('Y/m/d H:i:s')
                    ->size(TextColumnSize::Small)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        0 => __('site.inactive'),
                        1 => __('site.active'),
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
            'index' => Pages\ListTicketSubjects::route('/'),
            'create' => Pages\CreateTicketSubject::route('/create'),
            'edit' => Pages\EditTicketSubject::route('/{record}/edit'),
            'view' => Pages\ViewTicketSubject::route('/{record}'),
        ];
    }
}
