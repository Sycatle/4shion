<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\ProductVariant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Override;

final class LowStockVariants extends TableWidget
{
    #[Override]
    protected static ?int $sort = 2;

    #[Override]
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Stock faible')
            ->query(fn (): Builder => ProductVariant::query()
                ->with('product')
                ->whereRaw('stock <= COALESCE(low_stock_threshold, ?)', [Config::integer('shop.low_stock_threshold')])
                ->whereHas('product', fn (Builder $query) => $query->where('is_active', true)->where('is_gift_card', false)))
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
            ->recordUrl(fn (ProductVariant $record): string => ProductResource::getUrl('edit', ['record' => $record->product]))
            ->paginated([5, 10]);
    }
}
