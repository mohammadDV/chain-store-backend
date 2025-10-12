<?php

namespace App\Filament\Resources\TicketSubjectResource\Pages;

use App\Filament\Resources\TicketSubjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTicketSubject extends ViewRecord
{
    protected static string $resource = TicketSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
