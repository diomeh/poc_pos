<?php

namespace App\Filament\Resources\TransactionItems\Pages;

use App\Filament\Resources\TransactionItems\TransactionItemResource;
use Filament\Resources\Pages\ListRecords;

class ListTransactionItems extends ListRecords
{
    protected static string $resource = TransactionItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
