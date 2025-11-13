<?php

namespace App\Filament\Resources\WithdrawalTransactionResource\Pages;

use App\Filament\Resources\WithdrawalTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWithdrawalTransactions extends ListRecords
{
    protected static string $resource = WithdrawalTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - withdrawal transactions are created by users
        ];
    }
}
