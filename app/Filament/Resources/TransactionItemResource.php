<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionItemResource\Pages;
use App\Models\TransactionItem;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TransactionItemResource extends Resource
{
    protected static ?string $model = TransactionItem::class;

    protected static ?string $slug = 'transaction-items';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::InboxStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('transaction_id')
                    ->relationship('transaction', 'invoice_number')
                    ->searchable()
                    ->required(),

                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->required(),

                TextInput::make('qtty')
                    ->required()
                    ->integer(),

                TextInput::make('unit_price')
                    ->required()
                    ->numeric(),

                TextInput::make('discount')
                    ->required()
                    ->numeric(),

                TextInput::make('subtotal')
                    ->required()
                    ->numeric(),

                TextEntry::make('created_at')
                    ->label('Created Date')
                    ->state(fn(?TransactionItem $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                TextEntry::make('updated_at')
                    ->label('Last Modified Date')
                    ->state(fn(?TransactionItem $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction.invoice_number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('qtty'),

                TextColumn::make('unit_price'),

                TextColumn::make('discount'),

                TextColumn::make('subtotal'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTransactionItems::route('/'),
            'create' => Pages\CreateTransactionItem::route('/create'),
            'edit'   => Pages\EditTransactionItem::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['product']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['product.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->product) {
            $details['Product'] = $record->product->name;
        }

        return $details;
    }
}
