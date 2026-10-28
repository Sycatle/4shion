<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('orders:release-stale')->everyTenMinutes()->withoutOverlapping()->onOneServer();
Schedule::command('carts:remind-abandoned')->hourly()->withoutOverlapping()->onOneServer();
