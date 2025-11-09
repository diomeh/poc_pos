<?php

namespace App\Filament\Resources\Transactions\RelationManagers;

use App\Filament\Resources\TransactionItems\TransactionItemResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $relatedResource = TransactionItemResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
            ]);
    }
}
