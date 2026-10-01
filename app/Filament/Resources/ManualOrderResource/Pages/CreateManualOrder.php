<?php

namespace App\Filament\Resources\ManualOrderResource\Pages;

use App\Filament\Resources\ManualOrderResource;
use Domain\Product\Models\ManualOrder;
use Filament\Resources\Pages\CreateRecord;

class CreateManualOrder extends CreateRecord
{
    protected static string $resource = ManualOrderResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = ManualOrderResource::preparePersistedData($data);
        $data['code'] = ManualOrder::generateCode();
        $data['status'] = ManualOrder::PENDING;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
