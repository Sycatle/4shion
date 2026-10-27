<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Inbox of received Stripe events, keyed by the Stripe event id to drop redeliveries.
 */
#[Fillable(['id', 'type', 'processed_at'])]
class StripeEvent extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['processed_at' => 'datetime'];
    }
}
