<?php

namespace App\Filament\Resources\InventoryTransactionResource\Pages;

use Filament\Schemas\Schema;
use App\Filament\Resources\InventoryTransactionResource;
use Domain\Product\Enums\InventoryTransactionSource;
use Domain\Product\Enums\InventoryTransactionType;
use Domain\Product\Models\InventoryTransaction;
use Domain\Product\Models\Size;
use Domain\Product\Services\StockService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CreateInventoryTransaction extends CreateRecord
{
    protected static string $resource = InventoryTransactionResource::class;

    protected static bool $canCreateAnother = false;

    public function form(Schema $schema): Schema
    {
        return $schema->components(InventoryTransactionResource::adjustFormSchema());
    }

    public function getTitle(): string
    {
        return __('site.adjust_inventory');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $productId = (int) ($data['product_id'] ?? 0);
        $sizeId = (int) ($data['size_id'] ?? 0);
        $quantity = (int) ($data['quantity'] ?? 0);
        $typeValue = (string) ($data['type'] ?? InventoryTransactionType::Adjust->value);
        $type = InventoryTransactionType::tryFrom($typeValue) ?? InventoryTransactionType::Adjust;

        if ($productId < 1 || $sizeId < 1 || $quantity < 1) {
            throw ValidationException::withMessages([
                'product_ref' => __('site.product_not_found'),
            ]);
        }

        $sizeBelongsToProduct = Size::query()
            ->whereKey($sizeId)
            ->where('product_id', $productId)
            ->exists();

        if (! $sizeBelongsToProduct) {
            throw ValidationException::withMessages([
                'size_id' => __('site.product_not_found'),
            ]);
        }

        $forcedDirection = InventoryTransactionResource::forcedDirectionForType($type->value);
        $direction = $forcedDirection ?? ($data['direction'] ?? 'increase');

        if (! in_array($direction, ['increase', 'decrease'], true)) {
            throw ValidationException::withMessages([
                'direction' => __('site.inventory_direction_invalid'),
            ]);
        }

        if ($forcedDirection !== null && $direction !== $forcedDirection) {
            throw ValidationException::withMessages([
                'direction' => __('site.inventory_direction_locked_help'),
            ]);
        }

        $quantityChange = $direction === 'decrease' ? -$quantity : $quantity;

        try {
            /** @var InventoryTransaction $transaction */
            $transaction = app(StockService::class)->applyManualChange(
                $sizeId,
                $quantityChange,
                $type,
                InventoryTransactionSource::Admin,
                Auth::id(),
                $data['description'] ?? null,
            );
        } catch (RuntimeException $e) {
            Notification::make()
                ->title(__('site.Insufficient stock'))
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'quantity' => __('site.Insufficient stock'),
            ]);
        }

        return $transaction;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('site.inventory_adjusted_successfully');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
