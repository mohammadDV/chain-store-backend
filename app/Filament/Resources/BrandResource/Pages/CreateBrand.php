<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slug'] = Str::slug($data['slug'] ?: $data['title']);

        return $data;
    }
}