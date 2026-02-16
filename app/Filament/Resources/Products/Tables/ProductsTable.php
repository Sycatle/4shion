<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->label('')
                    ->collection('images')
                    ->limit(1),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->sortable(),
                TextColumn::make('base_price')
                    ->label('Prix')
                    ->money('EUR', divideBy: 100)
                    ->sortable(),
                TextColumn::make('variants_sum_stock')
                    ->label('Stock')
                    ->sum('variants', 'stock')
                    ->badge()
                    ->color(fn (int|string|null $state): string => (int) $state === 0 ? 'danger' : 'gray'),
                IconColumn::make('is_active')
                    ->label('Visible')
                    ->boolean(),
                IconColumn::make('is_featured')
                    ->label('Accueil')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('category')
                    ->label('Catégorie')
                    ->relationship('category', 'name'),
                TernaryFilter::make('is_active')
                    ->label('Visible'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
