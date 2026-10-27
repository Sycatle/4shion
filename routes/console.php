<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('orders:release-stale')->everyTenMinutes()->withoutOverlapping()->onOneServer();
