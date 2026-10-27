<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * Inbox of received Stripe events, keyed by the Stripe event id to drop redeliveries.
 */
#[Fillable(['id', 'type', 'processed_at'])]
#[WithoutIncrementing]
class StripeEvent extends Model
{
    #[Override]
    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['processed_at' => 'datetime'];
    }
}
