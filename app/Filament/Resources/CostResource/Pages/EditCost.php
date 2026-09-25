<?php

namespace App\Filament\Resources\CostResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\CostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCost extends EditRecord
{
    protected static string $resource = CostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
