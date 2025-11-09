<?php

namespace App\Filament\Resources\TransactionItems;

use App\Filament\Resources\Payments\RelationManagers\TransactionRelationManager;
use App\Filament\Resources\TransactionItems\Pages\ListTransactionItems;
use App\Filament\Resources\TransactionItems\Pages\ViewTransactionItem;
use App\Filament\Resources\TransactionItems\Schemas\TransactionItemForm;
use App\Filament\Resources\TransactionItems\Schemas\TransactionItemInfolist;
use App\Filament\Resources\TransactionItems\Tables\TransactionItemsTable;
use App\Models\TransactionItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TransactionItemResource extends Resource
{
    protected static ?string $model = TransactionItem::class;

    protected static ?string $navigationParentItem = 'Transactions';

    protected static string|UnitEnum|null $navigationGroup = 'Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Square3Stack3d;

    protected static ?string $recordTitleAttribute = 'Transaction Item';

    public static function form(Schema $schema): Schema
    {
        return TransactionItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TransactionItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransactionItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TransactionRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactionItems::route('/'),
            'view'  => ViewTransactionItem::route('/{record}'),
        ];
    }
}
