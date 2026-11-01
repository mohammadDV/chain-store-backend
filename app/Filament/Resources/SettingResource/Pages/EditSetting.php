<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use Domain\Setting\Models\Setting;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Never prefill the hashed password into the form.
        $data['invoice_unlock_password'] = null;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Remove security_code from data as it's not a database field
        unset($data['security_code']);

        if (! empty($data['invoice_unlock_password'])) {
            $data['invoice_unlock_password'] = Hash::make((string) $data['invoice_unlock_password']);
        } else {
            unset($data['invoice_unlock_password']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        /** @var Setting $record */
        $record = $this->getRecord();

        return $this->getResource()::getUrl('edit', ['record' => $record->id]);
    }
}
