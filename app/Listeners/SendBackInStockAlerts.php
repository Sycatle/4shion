<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\VariantRestocked;
use App\Mail\BackInStockNotice;
use App\Models\StockAlert;
use Illuminate\Support\Facades\Mail;

final class SendBackInStockAlerts
{
    public function handle(VariantRestocked $event): void
    {
        $event->variant->stockAlerts()
            ->whereNull('notified_at')
            ->each(function (StockAlert $alert): void {
                Mail::to($alert->email)->send(new BackInStockNotice($alert));
                $alert->update(['notified_at' => now()]);
            });
    }
}
