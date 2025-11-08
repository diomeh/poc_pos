<?php

namespace App\Filament\Resources\TransactionItems\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TransactionItemInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('transaction.invoice_number')
                    ->label('Transaction'),
                TextEntry::make('product.name')
                    ->label('Product'),
                TextEntry::make('qtty')
                    ->numeric(),
                TextEntry::make('unit_price')
                    ->numeric(),
                TextEntry::make('discount')
                    ->numeric(),
                TextEntry::make('subtotal')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
