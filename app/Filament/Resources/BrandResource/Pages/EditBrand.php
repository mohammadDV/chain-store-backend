<?php

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Domain\Seo\Support\AsciiSlug;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBrand extends EditRecord
{
    protected static string $resource = BrandResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $slug = trim((string) ($data['slug'] ?? ''));
        $data['slug'] = $slug !== ''
            ? AsciiSlug::normalize($slug)
            : AsciiSlug::generate((string) ($data['title'] ?? ''));

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
