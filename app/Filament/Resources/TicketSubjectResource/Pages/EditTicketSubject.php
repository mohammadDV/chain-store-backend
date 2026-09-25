<?php

namespace App\Filament\Resources\TicketSubjectResource\Pages;

use App\Filament\Resources\TicketSubjectResource;
use Filament\Actions\DeleteAction;
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
