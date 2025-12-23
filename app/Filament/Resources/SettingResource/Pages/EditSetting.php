<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use Domain\Setting\Models\Setting;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSetting extends EditRecord
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No delete action - settings cannot be deleted
        ];
    }

    public function mount(int|string $record = 1): void
    {
        // Always use the singleton instance (id = 1)
        $setting = Setting::getInstance();
        parent::mount($setting->id);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove security_code from data as it's not a database field
        unset($data['security_code']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record->id]);
    }
}
