<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\PageResource;
use Domain\Seo\Support\AsciiSlug;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $slug = trim((string) ($data['slug'] ?? ''));
        $data['slug'] = $slug !== ''
            ? AsciiSlug::normalize($slug)
            : AsciiSlug::generate((string) ($data['title'] ?? ''));

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
