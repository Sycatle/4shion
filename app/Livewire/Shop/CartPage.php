<?php

declare(strict_types=1);

namespace App\Livewire\Shop;

use App\Cart\CartManager;
use App\Catalog\Recommender;
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
final class CartPage extends Component
{
    private const int MAX_COUPON_ATTEMPTS_PER_MINUTE = 5;

    public string $couponCode = '';

    public ?string $couponError = null;

    public string $giftCardCode = '';

    public ?string $giftCardError = null;

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

    public function applyGiftCard(CartManager $cart): void
    {
        $this->giftCardError = null;

        // Gift card codes are as guessable as coupons: they share the same throttle.
        $key = 'coupon:'.session()->getId();

        if (RateLimiter::tooManyAttempts($key, self::MAX_COUPON_ATTEMPTS_PER_MINUTE)) {
            $this->giftCardError = sprintf('Trop de tentatives. Réessayez dans %d secondes.', RateLimiter::availableIn($key));

            return;
        }

        RateLimiter::hit($key);

        try {
            $cart->applyGiftCard($this->giftCardCode);
            $this->giftCardCode = '';
        } catch (CartException $e) {
            $this->giftCardError = $e->getMessage();
        }
    }

    public function removeGiftCard(CartManager $cart): void
    {
        $cart->removeGiftCard();
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

    public function render(CartManager $cart, Recommender $recommender): View
    {
        /** @var Collection<int, CartItem> $items */
        $items = $cart->items();

        return view('livewire.shop.cart-page', [
            'items' => $items,
            'suggestions' => $recommender->forCart(array_values($items->map(fn (CartItem $item): int => $item->variant->product_id)->unique()->all())),
            'coupon' => $cart->coupon(),
            'giftCard' => $cart->giftCard(),
            'totals' => $cart->totalsFor($items),
        ]);
    }
}
