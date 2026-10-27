<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class ShopStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Les 30 derniers jours';

    protected function getStats(): array
    {
        $since = now()->subDays(30);
        $paid = Order::query()
            ->whereIn('status', [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered])
            ->where('paid_at', '>=', $since);

        $revenue = (int) $paid->clone()->sum('total');
        $count = $paid->clone()->count();

        return [
            Stat::make('Chiffre d’affaires TTC', number_format($revenue / 100, 2, ',', ' ').' €'),
            Stat::make('Commandes payées', $count)
                ->description($count > 0 ? 'Panier moyen : '.number_format($revenue / $count / 100, 2, ',', ' ').' €' : null),
            Stat::make('À expédier', Order::query()->where('status', OrderStatus::Paid)->count())
                ->color('warning'),
        ];
    }
}
