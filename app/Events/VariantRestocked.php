<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ProductVariant;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** A sold-out variant has units available again. */
final class VariantRestocked implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public ProductVariant $variant) {}
}
