<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('view_ledger')
                ->label(__('site.view_order_ledger'))
                ->icon('heroicon-o-clock')
                ->url(fn (): string => OrderResource::getUrl('ledger', ['record' => $this->getRecord()])),
        ];
    }
}
