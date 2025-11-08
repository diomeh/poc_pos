<?php

namespace App\Filament\Resources\Transactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('invoice_number'),
                TextEntry::make('date')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('total')
                    ->numeric(),
                TextEntry::make('status')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('cashier.name')
                    ->label('Cashier'),
                TextEntry::make('customer.name')
                    ->label('Customer'),
                TextEntry::make('payment.id')
                    ->label('Payment')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
