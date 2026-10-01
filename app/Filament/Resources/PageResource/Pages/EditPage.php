<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\PageResource;
use Domain\Seo\Support\AsciiSlug;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

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
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
