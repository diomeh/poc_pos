<?php

namespace App\Filament\Resources\Payments\RelationManagers;

use App\Filament\Resources\Transactions\TransactionResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class TransactionRelationManager extends RelationManager
{
    protected static string $relationship = 'transaction';

    protected static ?string $relatedResource = TransactionResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
            ]);
    }
}
