<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Widgets\Concerns\UsesDashboardPeriod;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Override;

final class CouponPerformance extends TableWidget
{
    use UsesDashboardPeriod;

    #[Override]
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Chiffre d’affaires par code promo')
            ->query(fn (): Builder => Order::query()
                ->selectRaw('min(id) as id, coupon_code, count(*) as orders_count, sum(total) as revenue, sum(discount_total) as discounts')
                ->whereNotNull('coupon_code')
                ->whereIn('status', [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered])
                ->where('paid_at', '>=', $this->periodStart())
                ->groupBy('coupon_code'))
            ->columns([
                TextColumn::make('coupon_code')->label('Code'),
                TextColumn::make('orders_count')->label('Commandes')->sortable(),
                TextColumn::make('revenue')->label('CA TTC')->money('EUR', divideBy: 100)->sortable(),
                TextColumn::make('discounts')->label('Remises')->money('EUR', divideBy: 100)->sortable(),
            ])
            ->defaultKeySort(false)
            ->defaultSort('revenue', 'desc')
            ->emptyStateHeading('Aucun code utilisé sur la période')
            ->paginated([5]);
    }
}
