<?php

namespace App\Filament\Resources\ManualOrderResource\Pages;

use App\Filament\Resources\ManualOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListManualOrders extends ListRecords
{
    protected static string $resource = ManualOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
