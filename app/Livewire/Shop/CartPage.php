<?php

namespace App\Livewire\Shop;

use App\Cart\CartManager;
use App\Cart\CartTotals;
use App\Exceptions\Cart\CartException;
use App\Models\CartItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shop')]
#[Title('Panier')]
class CartPage extends Component
{
    private const MAX_COUPON_ATTEMPTS_PER_MINUTE = 5;

    public string $couponCode = '';

    public ?string $couponError = null;

    public ?string $error = null;

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

    public function applyCoupon(CartManager $cart): void
    {
        $this->couponError = null;

        // Coupon codes are guessable: throttle attempts per visitor.
        $key = 'coupon:'.session()->getId();

        if (RateLimiter::tooManyAttempts($key, self::MAX_COUPON_ATTEMPTS_PER_MINUTE)) {
            $this->couponError = sprintf('Trop de tentatives. Réessayez dans %d secondes.', RateLimiter::availableIn($key));

            return;
        }

        RateLimiter::hit($key);

        try {
            $cart->applyCoupon($this->couponCode);
            $this->couponCode = '';
        } catch (CartException $e) {
            $this->couponError = $e->getMessage();
        }
    }

    public function removeCoupon(CartManager $cart): void
    {
        $cart->removeCoupon();
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
        /** @var Collection<int, CartItem> $items */
        $items = $cart->items();
        $coupon = $cart->coupon();

        return view('livewire.shop.cart-page', [
            'items' => $items,
            'coupon' => $coupon,
            'totals' => CartTotals::calculate($items, $coupon),
        ]);
    }
}
