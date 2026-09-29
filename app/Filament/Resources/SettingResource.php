<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourceAuthorization;
use App\Filament\Resources\SettingResource\Pages\EditSetting;
use App\Filament\Resources\SettingResource\Pages\ListSettings;
use Closure;
use Domain\Setting\Models\Setting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    use ChecksResourceAuthorization;

    protected static ?string $model = Setting::class;

    protected static function permissionPrefix(): string
    {
        return 'settings';
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 100;

    public static function getNavigationLabel(): string
    {
        return __('site.settings');
    }

    public static function getModelLabel(): string
    {
        return __('site.setting');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('site.setting_information'))
                    ->schema([
                        TextInput::make('profit_rate')
                            ->label(__('site.profit_rate'))
                            ->numeric()
                            ->required()
                            ->step(0.01)
                            ->suffix('%')
                            ->helperText(__('site.profit_rate_help')),
                        TextInput::make('exchange_rate')
                            ->label(__('site.exchange_rate'))
                            ->numeric()
                            ->required()
                            ->step(0.01)
                            ->helperText(__('site.exchange_rate_help')),
                    ]),
                Section::make(__('site.payment_settings'))
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Toggle::make('payment_gateway_enabled')
                            ->label(__('site.payment_gateway_enabled'))
                            ->helperText(__('site.payment_gateway_enabled_help'))
                            ->default(true)
                            ->required(),
                    ]),
                Section::make(__('site.seo_settings'))
                    ->schema([
                        TextInput::make('site_name')
                            ->label(__('site.site_name'))
                            ->maxLength(255)
                            ->helperText(__('site.site_name_help')),
                        Textarea::make('default_meta_description')
                            ->label(__('site.default_meta_description'))
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText(__('site.default_meta_description_help')),
                        FileUpload::make('default_og_image')
                            ->label(__('site.default_og_image'))
                            ->image()
                            ->imageEditor()
                            ->disk('s3')
                            ->directory('settings/og')
                            ->visibility('public')
                            ->helperText(__('site.default_og_image_help')),
                    ]),
                Section::make(__('site.contact_page_settings'))
                    ->icon('heroicon-o-phone')
                    ->schema([
                        TextInput::make('contact_title')
                            ->label(__('site.contact_title'))
                            ->maxLength(255),
                        TextInput::make('contact_subtitle')
                            ->label(__('site.contact_subtitle'))
                            ->maxLength(255),
                        TextInput::make('contact_phone')
                            ->label(__('site.contact_phone'))
                            ->maxLength(255),
                        TextInput::make('contact_phone_hours')
                            ->label(__('site.contact_phone_hours'))
                            ->maxLength(255),
                        TextInput::make('contact_address')
                            ->label(__('site.contact_address'))
                            ->maxLength(255),
                        TextInput::make('contact_map_url')
                            ->label(__('site.contact_map_url'))
                            ->maxLength(2048),
                        TextInput::make('contact_email')
                            ->label(__('site.contact_email'))
                            ->email()
                            ->maxLength(255),
                        TextInput::make('contact_email_hint')
                            ->label(__('site.contact_email_hint'))
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make(__('site.security'))
                    ->schema([
                        TextInput::make('security_code')
                            ->label(__('site.security_code'))
                            ->password()
                            ->required()
                            ->helperText(__('site.security_code_help'))
                            ->rules([
                                function () {
                                    $securityCode = config('setting.security_code');

                                    return function (string $attribute, $value, Closure $fail) use ($securityCode) {
                                        if ($value !== $securityCode) {
                                            $fail(__('site.invalid_security_code'));
                                        }
                                    };
                                },
                            ])
                            ->dehydrated(false), // Don't save this field to the database
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            'index' => ListSettings::route('/'),
            'edit' => EditSetting::route('/1/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Settings cannot be created, only updated
    }

    public static function getNavigationUrl(): string
    {
        return static::getUrl('index');
    }
}
