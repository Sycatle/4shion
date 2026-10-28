<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Concerns;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/** The period chosen in the dashboard filter, in days (default 30). */
trait UsesDashboardPeriod
{
    use InteractsWithPageFilters;

    protected function periodDays(): int
    {
        // The select posts its value as a string; anything unusable falls back on 30 days.
        $raw = $this->pageFilters['days'] ?? 30;
        $days = is_scalar($raw) ? Str::of((string) $raw)->toInteger() : 0;

        return $days > 0 ? $days : 30;
    }

    protected function periodStart(): Carbon
    {
        return now()->subDays($this->periodDays())->startOfDay();
    }
}
