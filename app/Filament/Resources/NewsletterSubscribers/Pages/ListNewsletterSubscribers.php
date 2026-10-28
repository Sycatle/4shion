<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsletterSubscribers\Pages;

use App\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use Filament\Resources\Pages\ListRecords;
use Override;

final class ListNewsletterSubscribers extends ListRecords
{
    #[Override]
    protected static string $resource = NewsletterSubscriberResource::class;
}
