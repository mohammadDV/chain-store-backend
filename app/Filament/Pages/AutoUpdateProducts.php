<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProductResource;
use Domain\Brand\Models\Brand;
use Domain\Product\Exceptions\ProductScraperException;
use Domain\Product\Models\Category as ProductCategory;
use Domain\Product\Services\ProductScraperService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * @property-read Form $form
 */
class AutoUpdateProducts extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static string $view = 'filament.pages.auto-update-products';

    protected static ?int $navigationSort = 14;

    protected static ?string $slug = 'auto-update-products';

    public ?array $data = [];

    public ?array $preview = null;

    public ?string $previewFingerprint = null;

    public static function getNavigationGroup(): ?string
    {
        return __('site.product_management');
    }

    public static function getNavigationLabel(): string
    {
        return __('site.auto_update_products');
    }

    public function getTitle(): string
    {
        return __('site.auto_update_products');
    }

    public function mount(): void
    {
        $this->form->fill([
            'mode' => 'url',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('site.scraper_source'))
                    ->schema([
                        Forms\Components\Radio::make('mode')
                            ->label(__('site.scraper_mode'))
                            ->options([
                                'url' => __('site.scraper_mode_url'),
                                'code' => __('site.scraper_mode_code'),
                            ])
                            ->inline()
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetPreview())
                            ->required(),
                        Forms\Components\Select::make('brand_id')
                            ->label(__('site.brand'))
                            ->options(fn () => Brand::query()->orderBy('title')->pluck('title', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetPreview()),
                        Forms\Components\TextInput::make('url')
                            ->label(__('site.url'))
                            ->url()
                            ->nullable()
                            ->maxLength(2048)
                            ->visible(fn (Get $get) => $get('mode') === 'url')
                            ->required(fn (Get $get) => $get('mode') === 'url')
                            ->dehydrated(fn (Get $get) => $get('mode') === 'url')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn () => $this->resetPreview()),
                        Forms\Components\Select::make('category_id')
                            ->label(__('site.category'))
                            ->options(fn () => ProductCategory::query()->orderBy('title')->pluck('title', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => $get('mode') === 'url')
                            ->required(fn (Get $get) => $get('mode') === 'url')
                            ->dehydrated(fn (Get $get) => $get('mode') === 'url')
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetPreview()),
                        Forms\Components\TextInput::make('code')
                            ->label(__('site.code'))
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('mode') === 'code')
                            ->required(fn (Get $get) => $get('mode') === 'code')
                            ->dehydrated(fn (Get $get) => $get('mode') === 'code')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn () => $this->resetPreview()),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function previewProduct(ProductScraperService $scraper): void
    {
        $payload = $this->scraperPayload();

        try {
            $this->preview = $scraper->preview($payload);
            $this->previewFingerprint = $this->fingerprint($payload);
            Notification::make()
                ->title(__('site.scraper_preview_ready'))
                ->success()
                ->send();
        } catch (ProductScraperException $exception) {
            $this->resetPreview();
            Notification::make()
                ->title(__('site.scraper_preview_failed'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function applyUpdate(ProductScraperService $scraper): void
    {
        $payload = $this->scraperPayload();
        if ($this->preview === null || $this->previewFingerprint !== $this->fingerprint($payload)) {
            Notification::make()
                ->title(__('site.scraper_preview_required'))
                ->warning()
                ->send();

            return;
        }

        try {
            $result = $scraper->apply($payload);
            $storedId = $result['stored']['id'] ?? null;
            $created = (bool) ($result['stored']['created'] ?? false);
            $this->resetPreview();
            $notification = Notification::make()
                ->title($created ? __('site.scraper_created') : __('site.scraper_updated'))
                ->success();

            if ($storedId) {
                $notification->actions([
                    NotificationAction::make('view')
                        ->label(__('site.view_product'))
                        ->url(ProductResource::getUrl('edit', ['record' => $storedId])),
                ]);
            }

            $notification->send();
        } catch (ProductScraperException $exception) {
            Notification::make()
                ->title(__('site.scraper_apply_failed'))
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function scraperPayload(): array
    {
        $state = $this->form->getState();
        $mode = $state['mode'] ?? 'url';

        return app(ProductScraperService::class)->payload(
            $mode === 'url' ? ($state['url'] ?? null) : null,
            $mode === 'code' ? ($state['code'] ?? null) : null,
            (int) $state['brand_id'],
            $mode === 'url' ? ($state['category_id'] ?? null) : null,
        );
    }

    protected function fingerprint(array $payload): string
    {
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function resetPreview(): void
    {
        $this->preview = null;
        $this->previewFingerprint = null;
    }

    public function formatPreviewValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? __('site.yes') : __('site.no');
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '—';
        }

        return (string) $value;
    }
}
