<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListOrders extends ListRecords
{
    #[Override]
    protected static string $resource = OrderResource::class;
}
