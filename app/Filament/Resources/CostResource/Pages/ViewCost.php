<?php

namespace App\Filament\Resources\CostResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\CostResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCost extends ViewRecord
{
    protected static string $resource = CostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
