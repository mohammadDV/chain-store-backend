<?php

namespace App\Filament\Resources\CostCategoryResource\Pages;

use App\Filament\Resources\CostCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCostCategory extends EditRecord
{
    protected static string $resource = CostCategoryResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $parentId = $data['parent_id'] ?? 0;

        if ((int) $parentId === $this->record->getKey()) {
            $parentId = 0;
        }

        $data['parent_id'] = $parentId ?: 0;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
