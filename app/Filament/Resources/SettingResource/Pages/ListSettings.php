<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use Domain\Setting\Models\Setting;
use Filament\Resources\Pages\ListRecords;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;

    public function mount(): void
    {
        // Redirect directly to edit page since there's only one setting record
        $setting = Setting::getInstance();
        $this->redirectRoute('filament.admin.resources.settings.edit', ['record' => $setting->id]);
    }
}

