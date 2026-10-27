<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Override;

final class CreateProduct extends CreateRecord
{
    #[Override]
    protected static string $resource = ProductResource::class;
}
