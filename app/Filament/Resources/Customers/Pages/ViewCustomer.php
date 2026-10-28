<?php

declare(strict_types=1);

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\ViewRecord;
use Override;

final class ViewCustomer extends ViewRecord
{
    #[Override]
    protected static string $resource = CustomerResource::class;
}
