<?php

namespace App\Filament\Resources\TicketMessageResource\Pages;

use App\Filament\Resources\TicketMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTicketMessage extends ViewRecord
{
    protected static string $resource = TicketMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
