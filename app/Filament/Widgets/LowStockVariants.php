<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\ProductVariant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;

class LowStockVariants extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Stock faible')
            ->query(fn (): Builder => ProductVariant::query()
                ->with('product')
                ->where('stock', '<=', Config::integer('shop.low_stock_threshold'))
                ->whereHas('product', fn (Builder $query) => $query->where('is_active', true)))
            ->columns([
                TextColumn::make('product.name')->label('Produit'),
                TextColumn::make('sku')->label('SKU'),
                TextColumn::make('size')->label('Taille'),
                TextColumn::make('color')->label('Couleur'),
                TextColumn::make('stock')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'danger' : 'warning'),
            ])
            ->defaultSort('stock')
            ->recordUrl(fn (ProductVariant $record): string => ProductResource::getUrl('edit', ['record' => $record->product_id]))
            ->paginated([5, 10]);
    }
}
