<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourceAuthorization;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ManageUserPermissions;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use Domain\User\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class UserResource extends Resource
{
    use ChecksResourceAuthorization;

    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 3;

    protected static function permissionPrefix(): string
    {
        return 'users';
    }

    public static function getNavigationLabel(): string
    {
        return __('site.users');
    }

    public static function getModelLabel(): string
    {
        return __('site.user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.users');
    }

    /**
     * Deep-link URLs to related admin lists filtered by this user.
     * Pure URL building — no extra queries.
     *
     * @return array<string, array{label: string, icon: string, url: string}>
     */
    public static function relatedResourceLinks(int $userId): array
    {
        // Filament v5 binds ListRecords::$tableFilters to the `filters` query string key.
        $userFilter = [
            'filters' => [
                'user_id' => [
                    'value' => $userId,
                ],
            ],
        ];

        // Relative URLs so links work behind docker/nginx on :80 even when APP_URL points at :8000.
        return [
            'wallet' => [
                'label' => __('site.wallet'),
                'icon' => 'heroicon-o-wallet',
                'url' => WalletResource::getUrl('index', $userFilter, isAbsolute: false),
            ],
            'wallet_transactions' => [
                'label' => __('site.wallet_transactions'),
                'icon' => 'heroicon-o-arrows-right-left',
                'url' => WalletTransactionResource::getUrl('index', $userFilter, isAbsolute: false),
            ],
            'withdrawals' => [
                'label' => __('site.withdrawal_transactions'),
                'icon' => 'heroicon-o-arrow-down-tray',
                'url' => WithdrawalTransactionResource::getUrl('index', $userFilter, isAbsolute: false),
            ],
            'orders' => [
                'label' => __('site.orders'),
                'icon' => 'heroicon-o-shopping-bag',
                'url' => OrderResource::getUrl('index', $userFilter, isAbsolute: false),
            ],
            'transactions' => [
                'label' => __('site.transactions'),
                'icon' => 'heroicon-o-arrow-path',
                'url' => TransactionResource::getUrl('index', $userFilter, isAbsolute: false),
            ],
            'reviews' => [
                'label' => __('site.reviews'),
                'icon' => 'heroicon-o-chat-bubble-bottom-center-text',
                'url' => ReviewResource::getUrl('index', $userFilter, isAbsolute: false),
            ],
            'tickets' => [
                'label' => __('site.tickets'),
                'icon' => 'heroicon-o-ticket',
                'url' => TicketResource::getUrl('index', $userFilter, isAbsolute: false),
            ],
            'notifications' => [
                'label' => __('site.notifications'),
                'icon' => 'heroicon-o-bell',
                'url' => NotificationResource::getUrl('index', $userFilter, isAbsolute: false),
            ],
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('site.personal_information'))
                    ->schema([
                        TextInput::make('first_name')
                            ->label(__('site.first_name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('last_name')
                            ->label(__('site.last_name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('nickname')
                            ->label(__('site.nickname'))
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('site.email'))
                            ->email()
                            ->disabled()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('mobile')
                            ->label(__('site.mobile'))
                            ->maxLength(15),
                        TextInput::make('password')
                            ->label(__('site.Password'))
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                        Select::make('status')
                            ->label(__('site.status'))
                            ->options([
                                1 => __('site.Active'),
                                0 => __('site.Inactive'),
                            ])
                            ->default(1)
                            ->required(),
                        Toggle::make('is_private')
                            ->label(__('site.is_private'))
                            ->default(false),
                        Toggle::make('is_report')
                            ->label(__('site.is_report'))
                            ->default(false),
                    ])->columns(2),
                Section::make(__('site.images'))
                    ->schema([
                        FileUpload::make('profile_photo_path')
                            ->label(__('site.profile_photo_path'))
                            ->placeholder(__('site.upload_profile_photo'))
                            ->image()
                            ->imageEditor()
                            ->disk('s3')
                            ->directory('/users/profile-photos')
                            // ->previewable(false)
                            ->required(),
                        FileUpload::make('bg_photo_path')
                            ->label(__('site.bg_photo_path'))
                            ->placeholder(__('site.upload_bg_photo'))
                            ->disk('s3')
                            ->image()
                            ->directory('/users/bg-photos'),
                    ])->columns(2),

                Section::make(__('site.additional_information'))
                    ->schema([
                        TextInput::make('point')
                            ->label(__('site.point'))
                            ->numeric()
                            ->default(0),
                        TextInput::make('rate')
                            ->label(__('site.rate'))
                            ->numeric()
                            ->default(0),
                        TextInput::make('customer_number')
                            ->label(__('site.customer_number'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('google_id')
                            ->label(__('site.google_id'))
                            ->maxLength(255),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('site.table_id'))
                    ->sortable()
                    ->searchable(),
                ImageColumn::make('profile_photo_path')
                    ->label(__('site.profile_photo_path'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->disk('s3')
                    ->circular()
                    ->size(40)
                    ->extraImgAttributes(['loading' => 'lazy']), // HTML lazy loading attribute
                TextColumn::make('first_name')
                    ->label(__('site.first_name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('last_name')
                    ->label(__('site.last_name'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('nickname')
                    ->label(__('site.nickname'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('site.email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_number')
                    ->label(__('site.customer_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mobile')
                    ->label(__('site.mobile'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('site.status'))
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'success',
                        0 => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (int $state): string => $state === 1 ? __('site.Active') : __('site.Inactive')),
                TextColumn::make('level')
                    ->label(__('site.level'))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'gray',
                        2 => 'blue',
                        3 => 'green',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        1 => __('site.user_level_1'),
                        2 => __('site.user_level_2'),
                        3 => __('site.user_level_3'),
                        default => __('site.user_level_1'),
                    }),
                TextColumn::make('point')
                    ->label(__('site.point'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('site.created_at'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime('Y-m-d H:i:s') // Use native date formatting instead of Jalalian
                    ->sortable(),
                TextColumn::make('verified_at')
                    ->label(__('site.verified_at'))
                    ->dateTime('Y-m-d H:i:s') // Use native date formatting instead of Jalalian
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('email_verified_at')
                    ->label(__('site.email_verified_at'))
                    ->dateTime('Y-m-d H:i:s') // Use native date formatting instead of Jalalian
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('site.status'))
                    ->options([
                        1 => __('site.Active'),
                        0 => __('site.Inactive'),
                    ]),
                SelectFilter::make('level')
                    ->label(__('site.level'))
                    ->options([
                        1 => __('site.user_level_1'),
                        2 => __('site.user_level_2'),
                        3 => __('site.user_level_3'),
                    ]),
                Filter::make('created_at')
                    ->label(__('site.created_at'))
                    ->schema([
                        DatePicker::make('created_from')
                            ->label(__('site.from_date')),
                        DatePicker::make('created_until')
                            ->label(__('site.to_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ActionGroup::make(
                    collect(static::relatedResourceLinks(0))
                        ->map(fn (array $link, string $name): Action => Action::make('related_'.$name)
                            ->label($link['label'])
                            ->icon($link['icon'])
                            ->url(fn (User $record): string => static::relatedResourceLinks($record->id)[$name]['url']))
                        ->values()
                        ->all()
                )
                    ->label(__('site.user_related'))
                    ->icon('heroicon-o-squares-plus')
                    ->color('gray')
                    ->button(),
                ViewAction::make()
                    ->label(__('site.view_user')),
                EditAction::make()
                    ->label(__('site.edit_user')),
                Action::make('manage_permissions')
                    ->label(__('site.manage_permissions'))
                    ->icon('heroicon-o-shield-check')
                    ->color('warning')
                    ->url(fn (User $record): string => static::getUrl('permissions', ['record' => $record]))
                    ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->isSuperAdmin()),
                Action::make('verify_email')
                    ->label(__('site.verify_email'))
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading(__('site.confirm_verify_email'))
                    ->modalDescription(__('site.confirm_verify_email_description'))
                    ->modalSubmitActionLabel(__('site.verify_email'))
                    ->modalCancelActionLabel(__('site.cancel'))
                    ->action(function ($record) {
                        $record->update([
                            'email_verified_at' => now(),
                        ]);
                    })
                    ->after(function ($record) {
                        Notification::make()
                            ->title(__('site.email_verified_successfully'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => ! $record->email_verified_at),
                Action::make('verify_identity')
                    ->label(__('site.verify_identity'))
                    ->icon('heroicon-o-identification')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('site.confirm_verify_identity'))
                    ->modalDescription(__('site.confirm_verify_identity_description'))
                    ->modalSubmitActionLabel(__('site.verify_identity'))
                    ->modalCancelActionLabel(__('site.cancel'))
                    ->action(function ($record) {
                        $record->update([
                            'verified_at' => now(),
                        ]);
                    })
                    ->after(function ($record) {
                        Notification::make()
                            ->title(__('site.identity_verified_successfully'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->email_verified_at && ! $record->verified_at),
            ])
            ->toolbarActions([
                // Delete actions removed to disable user deletion
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100]) // Add pagination options
            ->persistFiltersInSession() // Persist filters in session
            ->persistSortInSession() // Persist sort in session
            ->poll('30s'); // Refresh data every 30 seconds for real-time updates
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
            'view' => ViewUser::route('/{record}'),
            'permissions' => ManageUserPermissions::route('/{record}/permissions'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Cache the user count for better performance
        return (string) Cache::remember('user_count', 300, function () {
            return static::getModel()::count();
        });
    }

    // Add query optimization method
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select(['id', 'first_name', 'last_name', 'nickname', 'email', 'mobile', 'status', 'level', 'point', 'created_at', 'verified_at', 'email_verified_at', 'profile_photo_path', 'customer_number'])
            ->without(['roles', 'permissions']) // Exclude unnecessary relationships
            ->with(['role:id,name']); // Only load essential role information
    }

    // Add table performance optimization
    public static function getTableQueryStringIdentifier(): string
    {
        return 'users';
    }
}
