<?php

namespace App\Livewire\Shop;

use App\Cart\CartManager;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCounter extends Component
{
    #[On('cart-updated')]
    public function refresh(): void
    {
        // Re-rendering is enough: the count is read in render().
    }

    public function render(CartManager $cart): View
    {
        return view('livewire.shop.cart-counter', ['count' => $cart->count()]);
    }
}
