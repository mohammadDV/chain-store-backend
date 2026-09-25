<?php

namespace App\Filament\Resources\CostResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\CostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCosts extends ListRecords
{
    protected static string $resource = CostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
