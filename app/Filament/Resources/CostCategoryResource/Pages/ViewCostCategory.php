<?php

namespace App\Filament\Resources\CostCategoryResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\CostCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCostCategory extends ViewRecord
{
    protected static string $resource = CostCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
