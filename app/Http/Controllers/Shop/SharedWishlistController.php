<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;

final class SharedWishlistController extends Controller
{
    public function __invoke(string $token): View
    {
        $owner = User::query()->where('wishlist_token', $token)->firstOrFail();

        return view('shop.shared-wishlist', [
            'owner' => $owner,
            'products' => $owner->wishlist()->active()->withRating()->with(['category', 'media', 'variants'])->latest('wishlist_items.created_at')->get(),
        ]);
    }
}
