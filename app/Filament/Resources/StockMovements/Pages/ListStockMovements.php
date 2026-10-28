<?php

declare(strict_types=1);

namespace App\Filament\Resources\StockMovements\Pages;

use App\Filament\Resources\StockMovements\StockMovementResource;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListStockMovements extends ListRecords
{
    #[Override]
    protected static string $resource = StockMovementResource::class;
}
