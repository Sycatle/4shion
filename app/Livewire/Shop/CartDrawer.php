<?php

declare(strict_types=1);

namespace App\Livewire\Shop;

use App\Cart\CartManager;
use App\Exceptions\Cart\CartException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

final class CartDrawer extends Component
{
    public ?string $error = null;

    #[On('cart-updated')]
    public function refresh(): void
    {
        // Re-rendering is enough: the items are read in render().
    }

    public function increment(CartManager $cart, int $itemId): void
    {
        $this->changeQuantity($cart, $itemId, 1);
    }

    public function decrement(CartManager $cart, int $itemId): void
    {
        $this->changeQuantity($cart, $itemId, -1);
    }

    public function remove(CartManager $cart, int $itemId): void
    {
        $cart->remove($itemId);
        $this->dispatch('cart-updated');
    }

    private function changeQuantity(CartManager $cart, int $itemId, int $delta): void
    {
        $this->error = null;
        $item = $cart->items()->firstWhere('id', $itemId);

        if ($item === null) {
            return;
        }

        try {
            $cart->updateQuantity($itemId, $item->quantity + $delta);
        } catch (CartException $e) {
            $this->error = $e->getMessage();
        }

        $this->dispatch('cart-updated');
    }

    public function render(CartManager $cart): View
    {
        $items = $cart->items();

        return view('livewire.shop.cart-drawer', [
            'items' => $items,
            'totals' => $cart->totalsFor($items),
        ]);
    }
}
