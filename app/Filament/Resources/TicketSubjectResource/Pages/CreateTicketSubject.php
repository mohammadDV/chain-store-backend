<?php

namespace App\Filament\Resources\TicketSubjectResource\Pages;

use App\Filament\Resources\TicketSubjectResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTicketSubject extends CreateRecord
{
    protected static string $resource = TicketSubjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }
}