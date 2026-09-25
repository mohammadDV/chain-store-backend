<?php

namespace App\Filament\Resources\TicketSubjectResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\TicketSubjectResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTicketSubject extends EditRecord
{
    protected static string $resource = TicketSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
