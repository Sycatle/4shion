<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListCustomers extends ListRecords
{
    #[Override]
    protected static string $resource = CustomerResource::class;
}
