<?php

namespace App\Filament\Resources\WithdrawalTransactionResource\Pages;

use App\Filament\Resources\WithdrawalTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWithdrawalTransaction extends ViewRecord
{
    protected static string $resource = WithdrawalTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No edit or delete actions - only complete/reject from table
        ];
    }
}
