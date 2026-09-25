<?php

namespace App\Filament\Resources\TicketSubjectResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\TicketSubjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTicketSubjects extends ListRecords
{
    protected static string $resource = TicketSubjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
