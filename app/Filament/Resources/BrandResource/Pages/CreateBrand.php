<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Domain\Seo\Support\AsciiSlug;
use Filament\Resources\Pages\CreateRecord;

class CreateBrand extends CreateRecord
{
    protected static string $resource = BrandResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $slug = trim((string) ($data['slug'] ?? ''));
        $data['slug'] = $slug !== ''
            ? AsciiSlug::normalize($slug)
            : AsciiSlug::generate((string) ($data['title'] ?? ''));

        return $data;
    }
}
