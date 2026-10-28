<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Widgets\Concerns\UsesDashboardPeriod;
use App\Models\Order;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Override;

final class ShopStats extends StatsOverviewWidget
{
    use UsesDashboardPeriod;

    #[Override]
    protected static ?int $sort = 1;

    #[Override]
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $since = $this->periodStart();
        $paid = Order::query()
            ->whereIn('status', [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered])
            ->where('paid_at', '>=', $since);

        $revenue = (int) $paid->clone()->sum('total');
        $discounts = (int) $paid->clone()->sum('discount_total');
        $count = $paid->clone()->count();

        return [
            Stat::make('Chiffre d’affaires TTC', $this->euros($revenue))
                ->description("Sur {$this->periodDays()} jours"),
            Stat::make('Commandes payées', $count)
                ->description($count > 0 ? 'Panier moyen : '.$this->euros(intdiv($revenue, $count)) : null),
            Stat::make('Remises accordées', $this->euros($discounts))
                ->description($revenue + $discounts > 0 ? round($discounts / ($revenue + $discounts) * 100, 1).' % du CA brut' : null),
            Stat::make('Nouveaux clients', User::query()->where('created_at', '>=', $since)->count()),
            Stat::make('À expédier', Order::query()->where('status', OrderStatus::Paid)->count())
                ->color('warning'),
        ];
    }

    private function euros(int $cents): string
    {
        return number_format($cents / 100, 2, ',', ' ').' €';
    }
}
