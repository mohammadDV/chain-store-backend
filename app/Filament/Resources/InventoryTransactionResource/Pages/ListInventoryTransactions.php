<?php

namespace App\Filament\Resources\InventoryTransactionResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\InventoryTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryTransactions extends ListRecords
{
    protected static string $resource = InventoryTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('site.adjust_inventory'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
