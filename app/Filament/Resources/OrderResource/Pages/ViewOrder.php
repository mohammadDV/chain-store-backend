<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Domain\Product\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Order $order */
        $order = $this->getRecord();

        return [
            EditAction::make(),
            Action::make('download_qr_pdf')
                ->label(__('site.download_qr_code'))
                ->icon('heroicon-o-qr-code')
                ->color('gray')
                ->visible(fn (): bool => filled($order->code))
                ->action(fn () => OrderResource::generateQrCodePdf($order)),
            Action::make('view_ledger')
                ->label(__('site.view_order_ledger'))
                ->icon('heroicon-o-clock')
                ->url(fn (): string => OrderResource::getUrl('ledger', ['record' => $order])),
        ];
    }
}
