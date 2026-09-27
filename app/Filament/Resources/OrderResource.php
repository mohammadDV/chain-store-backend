<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourceAuthorization;
use App\Filament\Filters\UserIdFilter;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\ManageOrderLedgers;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\OrderResource\RelationManagers\OrderProductsRelationManager;
use Core\Helpers\HelperClass;
use Domain\AdminAccess\AdminPermission;
use Domain\AdminAccess\Services\AdminAccessService;
use Domain\Product\Enums\OrderLedgerSource;
use Domain\Product\Exceptions\OrderAlreadyRefundedException;
use Domain\Product\Models\Color;
use Domain\Product\Models\Order;
use Domain\Product\Models\Size;
use Domain\Product\Services\OrderStatusService;
use Domain\User\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Morilog\Jalali\Jalalian;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class OrderResource extends Resource
{
    use ChecksResourceAuthorization;

    protected static ?string $model = Order::class;

    protected static function permissionPrefix(): string
    {
        return 'orders';
    }

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 12;

    public static function getNavigationLabel(): string
    {
        return __('site.orders');
    }

    public static function getModelLabel(): string
    {
        return __('site.order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('site.orders');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('site.order_information'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('code')
                                    ->label(__('site.order_code'))
                                    ->disabled()
                                    ->dehydrated(false),
                                Select::make('user_id')
                                    ->label(__('site.user'))
                                    ->relationship('user', 'nickname', fn ($query) => $query->whereNotNull('nickname'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('product_count')
                                    ->label(__('site.product_count'))
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('total_amount')
                                    ->label(__('site.total_amount'))
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefix('$'),
                                TextInput::make('discount_amount')
                                    ->label(__('site.discount_amount'))
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefix('$'),
                                TextInput::make('amount')
                                    ->label(__('site.amount'))
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefix('$'),
                            ]),
                        // Select::make('status')
                        //     ->label(__('site.status'))
                        //     ->options([
                        //         Order::PENDING => __('site.pending'),
                        //         Order::PAID => __('site.paid'),
                        //         Order::CANCELLED => __('site.cancelled'),
                        //         Order::SHIPPED => __('site.shipped'),
                        //         Order::DELIVERED => __('site.delivered'),
                        //         Order::RETURNED => __('site.returned'),
                        //         Order::REFUNDED => __('site.refunded'),
                        //         Order::FAILED => __('site.failed'),
                        //         Order::EXPIRED => __('site.expired'),
                        //     ])
                        //     ->required()
                        //     ->native(false),
                        Textarea::make('description')
                            ->label(__('site.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make(__('site.shipping_information'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('fullname')
                                    ->label(__('site.fullname'))
                                    ->maxLength(255),
                                TextInput::make('postal_code')
                                    ->label(__('site.postal_code'))
                                    ->maxLength(255),
                            ]),
                        Textarea::make('address')
                            ->label(__('site.address'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make(__('site.additional_information'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('active')
                                    ->label(__('site.active'))
                                    ->default(true),
                                Toggle::make('vip')
                                    ->label(__('site.vip'))
                                    ->default(false),
                            ]),
                    ]),
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
                TextColumn::make('code')
                    ->label(__('site.order_code'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage(__('site.copied'))
                    ->copyMessageDuration(1500),
                TextColumn::make('user.nickname')
                    ->label(__('site.user'))
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => route('filament.admin.resources.users.view', ['record' => $record->user_id]))
                    ->openUrlInNewTab(),
                TextColumn::make('product_count')
                    ->label(__('site.product_count'))
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('total_amount')
                    ->label(__('site.total_amount'))
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('discount_amount')
                    ->label(__('site.discount_amount'))
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('amount')
                    ->label(__('site.amount'))
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('site.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Order::PAID => 'success',
                        Order::DELIVERED => 'success',
                        Order::SHIPPED => 'info',
                        Order::PENDING => 'warning',
                        Order::CANCELLED => 'danger',
                        Order::RETURNED => 'warning',
                        Order::REFUNDED => 'gray',
                        Order::FAILED => 'danger',
                        Order::EXPIRED => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Order::PENDING => __('site.pending'),
                        Order::PAID => __('site.paid'),
                        Order::CANCELLED => __('site.cancelled'),
                        Order::SHIPPED => __('site.shipped'),
                        Order::DELIVERED => __('site.delivered'),
                        Order::RETURNED => __('site.returned'),
                        Order::REFUNDED => __('site.refunded'),
                        Order::FAILED => __('site.failed'),
                        Order::EXPIRED => __('site.expired'),
                        default => $state,
                    }),
                TextColumn::make('fullname')
                    ->label(__('site.fullname'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('vip')
                    ->label(__('site.vip'))
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('active')
                    ->label(__('site.active'))
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                        Order::PENDING => __('site.pending'),
                        Order::PAID => __('site.paid'),
                        Order::CANCELLED => __('site.cancelled'),
                        Order::SHIPPED => __('site.shipped'),
                        Order::DELIVERED => __('site.delivered'),
                        Order::RETURNED => __('site.returned'),
                        Order::REFUNDED => __('site.refunded'),
                        Order::FAILED => __('site.failed'),
                        Order::EXPIRED => __('site.expired'),
                    ]),
                UserIdFilter::make(),
                SelectFilter::make('vip')
                    ->label(__('site.vip'))
                    ->options([
                        1 => __('site.yes'),
                        0 => __('site.no'),
                    ]),
                SelectFilter::make('active')
                    ->label(__('site.active'))
                    ->options([
                        1 => __('site.Active'),
                        0 => __('site.Inactive'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('download_pdf')
                    ->label(__('site.download_invoice'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($record) {
                        return static::generateInvoicePdf($record);
                    }),
                Action::make('view_ledger')
                    ->label(__('site.view_order_ledger'))
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->url(fn (Order $record): string => static::getUrl('ledger', ['record' => $record])),
                Action::make('change_status')
                    ->label(__('site.change_status'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->hidden(fn (Order $record): bool => $record->status === Order::REFUNDED)
                    ->requiresConfirmation()
                    ->modalHeading(__('site.confirm_status_change'))
                    ->modalDescription(__('site.confirm_status_change_description'))
                    ->visible(function (): bool {
                        $user = Auth::user();

                        return $user instanceof User
                            && ($user->can(AdminPermission::ORDERS_CHANGE_STATUS) || $user->can(AdminPermission::ORDERS_REFUND));
                    })
                    ->schema([
                        Select::make('status')
                            ->label(__('site.status'))
                            ->options(function (): array {
                                $options = [
                                    Order::PENDING => __('site.pending'),
                                    Order::PAID => __('site.paid'),
                                    Order::CANCELLED => __('site.cancelled'),
                                    Order::SHIPPED => __('site.shipped'),
                                    Order::DELIVERED => __('site.delivered'),
                                    Order::RETURNED => __('site.returned'),
                                    Order::FAILED => __('site.failed'),
                                    Order::EXPIRED => __('site.expired'),
                                ];

                                $user = Auth::user();
                                if ($user instanceof User && $user->can(AdminPermission::ORDERS_REFUND)) {
                                    $options[Order::REFUNDED] = __('site.refunded');
                                }

                                return $options;
                            })
                            ->default(fn (Order $record) => $record->status)
                            ->required()
                            ->native(false),
                        Textarea::make('message')
                            ->label(__('site.ledger_message'))
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->action(function (Order $record, array $data) {
                        $user = Auth::user();
                        if (! $user instanceof User) {
                            return;
                        }

                        if ($data['status'] === Order::REFUNDED) {
                            if (! $user->can(AdminPermission::ORDERS_REFUND)) {
                                Notification::make()
                                    ->title(__('site.error'))
                                    ->body(__('site.unauthorized'))
                                    ->danger()
                                    ->send();

                                return;
                            }
                        } elseif (! $user->can(AdminPermission::ORDERS_CHANGE_STATUS)) {
                            Notification::make()
                                ->title(__('site.error'))
                                ->body(__('site.unauthorized'))
                                ->danger()
                                ->send();

                            return;
                        }

                        try {
                            app(OrderStatusService::class)->transition(
                                order: $record,
                                toStatus: (string) $data['status'],
                                source: OrderLedgerSource::Admin,
                                actor: $user,
                                message: $data['message'] ?? null,
                            );
                        } catch (OrderAlreadyRefundedException|\InvalidArgumentException $e) {
                            Notification::make()
                                ->title(__('site.error'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title(__('site.status_updated_successfully'))
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            OrderProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
            'ledger' => ManageOrderLedgers::route('/{record}/ledger'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['user:id,nickname,first_name,last_name', 'products:id,title,code,image,url,status']);

        $user = Auth::user();
        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return app(AdminAccessService::class)->scopeBrandQuery($query, $user, 'products');
    }

    /**
     * Generate and download invoice PDF
     *
     * @param  Order  $order
     */
    public static function generateInvoicePdf($order): StreamedResponse
    {
        // Load order with necessary relationships
        // Note: discount_amount may not exist in products table, so we don't eager load it
        // Include brand_id in products select to enable brand relationship eager loading
        $order->load([
            'user:id,first_name,last_name,nickname,mobile',
            'products:id,title,code,image,url,status,amount,discount,brand_id',
            'products.brand:id,title',
            'discount:id,code',
        ]);

        // Collect all unique color_id and size_id from pivot to avoid N+1 queries
        $colorIds = [];
        $sizeIds = [];
        foreach ($order->products as $product) {
            if ($product->pivot->color_id) {
                $colorIds[] = $product->pivot->color_id;
            }
            if ($product->pivot->size_id) {
                $sizeIds[] = $product->pivot->size_id;
            }
        }

        // Fetch all colors and sizes in bulk
        $colors = [];
        $sizes = [];

        if (! empty($colorIds)) {
            $colorModels = Color::whereIn('id', array_unique($colorIds))->get(['id', 'title']);
            foreach ($colorModels as $color) {
                $colors[$color->id] = $color->title;
            }
        }

        if (! empty($sizeIds)) {
            $sizeModels = Size::whereIn('id', array_unique($sizeIds))->get(['id', 'title']);
            foreach ($sizeModels as $size) {
                $sizes[$size->id] = $size->title;
            }
        }

        $tempDir = storage_path('app/tmp');
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $productImages = [];
        $tempImageFiles = [];
        foreach ($order->products as $product) {
            $imagePath = static::resolveProductImagePath($product->image, $tempDir);
            if ($imagePath) {
                $productImages[$product->id] = $imagePath;
                $tempImageFiles[] = $imagePath;
            }
        }

        // Convert amount to Persian words
        $amountInWords = HelperClass::numberToPersianWords((float) ($order->amount ?? 0));

        // Render the view
        $html = View::make('pdf.invoice', [
            'order' => $order,
            'amountInWords' => $amountInWords,
            'colors' => $colors,
            'sizes' => $sizes,
            'productImages' => $productImages,
        ])->render();

        $defaultConfig = (new ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        // Configure mpdf with Vazirmatn (Persian digits) for proper RTL typography
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'fontDir' => array_merge($fontDirs, [
                resource_path('fonts/vazirmatn'),
            ]),
            'fontdata' => $fontData + [
                'vazirmatn' => [
                    'R' => 'Vazirmatn-FD-Regular.ttf',
                    'B' => 'Vazirmatn-FD-Bold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
            'default_font' => 'vazirmatn',
            'directionality' => 'rtl',
            'tempDir' => $tempDir,
        ]);

        try {
            if (preg_match('/<style\b[^>]*>(.*?)<\/style>/is', $html, $styleMatch)) {
                $mpdf->WriteHTML($styleMatch[1], HTMLParserMode::HEADER_CSS);
            }

            if (preg_match('/<body\b[^>]*>(.*)<\/body>/is', $html, $bodyMatch)) {
                $mpdf->WriteHTML($bodyMatch[1], HTMLParserMode::HTML_BODY);
            } else {
                $mpdf->WriteHTML($html);
            }

            $pdfContent = $mpdf->Output('', 'S');
        } finally {
            foreach ($tempImageFiles as $tempImageFile) {
                if (is_file($tempImageFile)) {
                    @unlink($tempImageFile);
                }
            }
        }

        $jalaliDate = Jalalian::fromDateTime($order->created_at)->format('Y-m-d');
        $filename = 'invoice-'.$jalaliDate.'-'.$order->code.'.pdf';

        return response()->streamDownload(function () use ($pdfContent) {
            echo $pdfContent;
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Resolve a product image to a local temp file path for reliable mPDF embedding.
     */
    protected static function resolveProductImagePath(?string $image, string $tempDir): ?string
    {
        if (! $image) {
            return null;
        }

        try {
            $contents = static::fetchProductImageBinary($image);
            if (! $contents) {
                return null;
            }

            $contents = static::compressImageForPdf($contents) ?? $contents;
            $path = $tempDir.'/invoice-img-'.uniqid('', true).'.jpg';

            if (file_put_contents($path, $contents) === false) {
                return null;
            }

            return $path;
        } catch (Throwable) {
            return null;
        }
    }

    protected static function fetchProductImageBinary(string $image): ?string
    {
        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            $url = html_entity_decode($image, ENT_QUOTES | ENT_HTML5);
            $context = stream_context_create([
                'http' => [
                    'timeout' => 8,
                    'follow_location' => 1,
                    'user_agent' => 'BoofstoreInvoice/1.0',
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $contents = @file_get_contents($url, false, $context);

            return $contents === false ? null : $contents;
        }

        foreach (['s3', 'public', 'liara', 'digitalocean'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($image)) {
                    return Storage::disk($disk)->get($image);
                }
            } catch (Throwable) {
                continue;
            }
        }

        $localPath = storage_path('app/public/'.ltrim($image, '/'));
        if (is_file($localPath)) {
            $contents = file_get_contents($localPath);

            return $contents === false ? null : $contents;
        }

        return null;
    }

    /**
     * Downscale/re-encode images for smaller PDF payloads.
     */
    protected static function compressImageForPdf(string $binary): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return null;
        }

        $maxSide = 180;
        $width = imagesx($source);
        $height = imagesy($source);

        $scale = min(1, $maxSide / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 80);
        $compressed = ob_get_clean() ?: null;

        imagedestroy($source);
        imagedestroy($canvas);

        return $compressed ?: null;
    }
}
