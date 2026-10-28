<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Widgets\Concerns\UsesDashboardPeriod;
use App\Models\OrderItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Override;

final class TopProducts extends TableWidget
{
    use UsesDashboardPeriod;

    #[Override]
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Meilleures ventes')
            ->query(fn (): Builder => OrderItem::query()
                ->selectRaw('min(order_items.id) as id, order_items.product_name, sum(order_items.quantity) as units, sum(order_items.total) as revenue')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereIn('orders.status', [OrderStatus::Paid->value, OrderStatus::Shipped->value, OrderStatus::Delivered->value])
                ->where('orders.paid_at', '>=', $this->periodStart())
                ->groupBy('order_items.product_name'))
            ->columns([
                TextColumn::make('product_name')->label('Produit'),
                TextColumn::make('units')->label('Unités')->sortable(),
                TextColumn::make('revenue')->label('CA TTC')->money('EUR', divideBy: 100)->sortable(),
            ])
            ->emptyStateHeading('Aucune vente sur la période')
            ->defaultKeySort(false)
            ->defaultSort('units', 'desc')
            ->paginated([5]);
    }
}
