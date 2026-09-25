<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Domain\User\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        /** @var User $user */
        $user = $this->getRecord();
        $links = UserResource::relatedResourceLinks($user->id);

        $relatedActions = [];
        foreach ($links as $name => $link) {
            $relatedActions[] = Actions\Action::make('related_'.$name)
                ->label($link['label'])
                ->icon($link['icon'])
                ->url($link['url']);
        }

        return [
            Actions\ActionGroup::make($relatedActions)
                ->label(__('site.user_related'))
                ->icon('heroicon-o-squares-plus')
                ->color('gray')
                ->button(),
            Actions\EditAction::make()
                ->label(__('site.edit_user')),
        ];
    }

    public function getTitle(): string
    {
        return __('site.view_user');
    }
}
