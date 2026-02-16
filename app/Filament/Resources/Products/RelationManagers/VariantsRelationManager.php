<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variantes';

    protected static ?string $modelLabel = 'variante';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('stock')
                    ->label('Stock')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('size')
                    ->label('Taille')
                    ->required()
                    ->maxLength(255),
                TextInput::make('color')
                    ->label('Couleur')
                    ->required()
                    ->maxLength(255),
                ColorPicker::make('color_hex')
                    ->label('Pastille'),
                TextInput::make('price_override')
                    ->label('Prix spécifique TTC')
                    ->helperText('Laisser vide pour utiliser le prix du produit.')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->suffix('€')
                    ->formatStateUsing(fn (?int $state): ?float => $state === null ? null : $state / 100)
                    ->dehydrateStateUsing(fn (float|int|string|null $state): ?int => $state === null || $state === '' ? null : (int) round(((float) $state) * 100)),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                ColorColumn::make('color_hex')
                    ->label(''),
                TextColumn::make('color')
                    ->label('Couleur')
                    ->searchable(),
                TextColumn::make('size')
                    ->label('Taille'),
                TextColumn::make('price_override')
                    ->label('Prix spécifique')
                    ->money('EUR', divideBy: 100)
                    ->placeholder('—'),
                TextColumn::make('stock')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state <= config('shop.low_stock_threshold') => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
            ])
            ->defaultSort('id')
            ->headerActions([
                CreateAction::make(),
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
}
