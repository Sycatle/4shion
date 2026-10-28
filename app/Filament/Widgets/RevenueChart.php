<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Widgets\Concerns\UsesDashboardPeriod;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Override;

final class RevenueChart extends ChartWidget
{
    use UsesDashboardPeriod;

    #[Override]
    protected static ?int $sort = 2;

    #[Override]
    protected int|string|array $columnSpan = 'full';

    #[Override]
    protected ?string $heading = 'Chiffre d’affaires par jour (TTC)';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $since = $this->periodStart();

        /** @var array<string, int|string> $byDay */
        $byDay = Order::query()
            ->whereIn('status', [OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Delivered])
            ->where('paid_at', '>=', $since)
            ->selectRaw('date(paid_at) as day, sum(total) as revenue')
            ->groupBy(DB::raw('date(paid_at)'))
            ->pluck('revenue', 'day')
            ->all();

        $labels = [];
        $values = [];

        for ($day = $since->copy(); $day->lte(now()); $day->addDay()) {
            $labels[] = $day->format('d/m');
            $values[] = round((int) ($byDay[$day->toDateString()] ?? 0) / 100, 2);
        }

        return [
            'datasets' => [['label' => 'CA TTC (€)', 'data' => $values, 'tension' => 0.3]],
            'labels' => $labels,
        ];
    }
}
