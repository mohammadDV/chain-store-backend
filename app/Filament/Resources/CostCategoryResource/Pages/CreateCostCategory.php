<?php

namespace App\Filament\Resources\CostCategoryResource\Pages;

use App\Filament\Resources\CostCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCostCategory extends CreateRecord
{
    protected static string $resource = CostCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['parent_id'] = (int) ($data['parent_id'] ?? 0);

        return $data;
    }
}