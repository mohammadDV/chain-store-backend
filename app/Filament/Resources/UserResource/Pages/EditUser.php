<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Domain\User\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manage_permissions')
                ->label(__('site.manage_permissions'))
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->url(fn (): string => UserResource::getUrl('permissions', ['record' => $this->record]))
                ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->isSuperAdmin()),
        ];
    }

    public function getTitle(): string
    {
        return __('site.edit_user');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
