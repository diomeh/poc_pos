<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Enums\TransactionStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('invoice_number')
                    ->required(),
                DateTimePicker::make('date'),
                TextInput::make('total')
                    ->required()
                    ->numeric(),
                Select::make('status')
                    ->options(TransactionStatus::class),
                Select::make('cashier_id')
                    ->relationship('cashier', 'name')
                    ->required(),
                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->required(),
                Select::make('payment_id')
                    ->relationship('payment', 'id'),
            ]);
    }
}
