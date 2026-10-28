<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shop')]
#[Title('Mes favoris')]
final class Wishlist extends Component
{
    public function remove(int $productId): void
    {
        $this->user()->wishlist()->detach($productId);
    }

    public function render(): View
    {
        $user = $this->user();

        /** @var Collection<int, Product> $products */
        $products = $user->wishlist()->active()->withRating()->with(['category', 'media', 'variants'])->latest('wishlist_items.created_at')->get();

        return view('livewire.account.wishlist', [
            'products' => $products,
            'shareUrl' => route('wishlist.shared', $user->wishlistToken()),
        ]);
    }

    private function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
