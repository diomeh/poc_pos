<?php

namespace App\Filament\Resources\TransactionItems\Pages;

use App\Filament\Resources\TransactionItems\TransactionItemResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTransactionItem extends ViewRecord
{
    protected static string $resource = TransactionItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
