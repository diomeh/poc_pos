<?php

namespace App\Filament\Resources\TransactionItemResource\Pages;

use App\Filament\Resources\TransactionItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTransactionItem extends CreateRecord
{
    protected static string $resource = TransactionItemResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
