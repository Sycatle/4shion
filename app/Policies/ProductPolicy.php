<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

final class ProductPolicy
{
    /**
     * Only customers who bought the product may review it, once.
     */
    public function review(User $user, Product $product): bool
    {
        return $user->hasPurchased($product)
            && ! $product->reviews()->where('user_id', $user->id)->exists();
    }
}
