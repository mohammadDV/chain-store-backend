<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ReviewResource\Pages\ListReviews;
use App\Filament\Resources\ReviewResource\Pages\CreateReview;
use App\Filament\Resources\ReviewResource\Pages\ViewReview;
use App\Filament\Resources\ReviewResource\Pages\EditReview;
use App\Filament\Filters\UserIdFilter;
use App\Filament\Resources\ReviewResource\Pages;
use Domain\Review\Models\Review;
use Domain\User\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 14;

    public static function getNavigationLabel(): string
    {
        return __('site.reviews');
    }

    public static function getModelLabel(): string
    {
        return __('site.review');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.reviews');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('site.review_information'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('user_id')
                                    ->label(__('site.user'))
                                    ->relationship('user', 'id')
                                    ->searchable()
                                    ->preload()
                                    ->getOptionLabelFromRecordUsing(fn (User $record) => $record->getFilamentName())
                                    ->required(),
                                Select::make('product_id')
                                    ->label(__('site.product'))
                                    ->relationship('product', 'title')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('rate')
                                    ->label(__('site.rate'))
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(5)
                                    ->required(),
                                Toggle::make('active')
                                    ->label(__('site.active'))
                                    ->default(true),
                                Select::make('status')
                                    ->label(__('site.status'))
                                    ->options([
                                        Review::PENDING => __('site.pending'),
                                        Review::APPROVED => __('site.approved'),
                                        Review::CANCELLED => __('site.cancelled'),
                                    ])
                                    ->required(),
                            ]),
                        Textarea::make('comment')
                            ->label(__('site.comment'))
                            ->rows(4)
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('site.table_id'))
                    ->sortable(),
                TextColumn::make('product.title')
                    ->label(__('site.product'))
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('user.email')
                    ->label(__('site.user'))
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('rate')
                    ->label(__('site.rate'))
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('site.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Review::APPROVED => 'success',
                        Review::PENDING => 'warning',
                        Review::CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Review::APPROVED => __('site.approved'),
                        Review::PENDING => __('site.pending'),
                        Review::CANCELLED => __('site.cancelled'),
                        default => $state,
                    }),
                IconColumn::make('active')
                    ->label(__('site.active'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),
                TextColumn::make('created_at')
                    ->label(__('site.created_at'))
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('site.status'))
                    ->options([
                        Review::PENDING => __('site.pending'),
                        Review::APPROVED => __('site.approved'),
                        Review::CANCELLED => __('site.cancelled'),
                    ]),
                SelectFilter::make('product_id')
                    ->label(__('site.product'))
                    ->relationship('product', 'title'),
                UserIdFilter::make(),
                TernaryFilter::make('active')
                    ->label(__('site.active')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('approve')
                    ->label(__('site.approve'))
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn (Review $record): bool => $record->status !== Review::APPROVED)
                    ->requiresConfirmation()
                    ->action(fn (Review $record) => $record->update(['status' => Review::APPROVED])),
                Action::make('reject')
                    ->label(__('site.reject'))
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn (Review $record): bool => $record->status !== Review::CANCELLED)
                    ->requiresConfirmation()
                    ->action(fn (Review $record) => $record->update(['status' => Review::CANCELLED])),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => ListReviews::route('/'),
            'create' => CreateReview::route('/create'),
            'view' => ViewReview::route('/{record}'),
            'edit' => EditReview::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()
            ->where('status', Review::PENDING)
            ->count();
    }
}
