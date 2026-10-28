<?php

declare(strict_types=1);

namespace App\Cart;

use App\Exceptions\Cart\CartException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\GiftCard;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Support\Money;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Persistent cart: bound to the user when authenticated, to a session token otherwise.
 */
final class CartManager
{
    public const int MAX_QUANTITY_PER_LINE = 10;

    private const string SESSION_KEY = 'cart_token';

    private ?Cart $cart = null;

    public function __construct(private readonly Guard $auth, private readonly Session $session) {}

    /** Cart of the current visitor, without creating one. */
    public function find(): ?Cart
    {
        if ($this->cart instanceof Cart) {
            return $this->cart;
        }

        $user = $this->auth->user();
        $token = $this->session->get(self::SESSION_KEY);

        $cart = match (true) {
            $user !== null => Cart::query()->where('user_id', $user->getAuthIdentifier())->first(),
            is_string($token) => Cart::query()->where('token', $token)->whereNull('user_id')->first(),
            default => null,
        };

        return $this->cart = $cart;
    }

    private function findOrCreate(): Cart
    {
        if (($cart = $this->find()) instanceof Cart) {
            return $cart;
        }

        $user = $this->auth->user();

        if ($user === null) {
            $token = (string) Str::uuid();
            $this->session->put(self::SESSION_KEY, $token);
        }

        return $this->cart = Cart::create([
            'user_id' => $user?->getAuthIdentifier(),
            'token' => $token ?? null,
        ]);
    }

    /** @return Collection<int, CartItem> */
    public function items(): Collection
    {
        return $this->find()?->items()->with('variant.product.media')->orderBy('id')->get() ?? new Collection;
    }

    public function coupon(): ?Coupon
    {
        return $this->find()?->coupon?->loadMissing('categories');
    }

    public function giftCard(): ?GiftCard
    {
        return $this->find()?->giftCard;
    }

    public function totals(): CartTotals
    {
        return $this->totalsFor($this->items());
    }

    /**
     * @param  Collection<int, CartItem>  $items  the cart items, already loaded by the caller
     */
    public function totalsFor(Collection $items, ?GiftCard $giftCard = null, ?ShippingMethod $shippingMethod = null): CartTotals
    {
        $user = $this->auth->user();

        return CartTotals::calculate(
            $items,
            $this->coupon(),
            Promotion::query()->running()->get(),
            $giftCard ?? $this->giftCard(),
            $user instanceof User ? $user : null,
            $shippingMethod ?? ShippingMethod::default(),
        );
    }

    public function count(): int
    {
        return (int) ($this->find()?->items()->sum('quantity') ?? 0);
    }

    public function add(ProductVariant $variant, int $quantity = 1): CartItem
    {
        $cart = $this->findOrCreate();
        $item = $cart->items()->firstOrNew(['product_variant_id' => $variant->id], ['quantity' => 0]);

        $item->quantity = $this->clampedQuantity($variant, $item->quantity + $quantity);
        $item->save();

        return $item;
    }

    public function updateQuantity(int $itemId, int $quantity): void
    {
        $item = $this->find()?->items()->with('variant')->find($itemId);

        if ($item === null) {
            return;
        }

        if ($quantity <= 0) {
            $item->delete();

            return;
        }

        $item->update(['quantity' => $this->clampedQuantity($item->variant, $quantity)]);
    }

    public function remove(int $itemId): void
    {
        $this->find()?->items()->whereKey($itemId)->delete();
    }

    public function applyCoupon(string $code): Coupon
    {
        $cart = $this->find();

        $coupon = Coupon::query()->where('code', Str::upper(trim($code)))->first();

        if (! $cart instanceof Cart || $coupon === null || ! $coupon->isUsable()) {
            throw CartException::invalidCoupon();
        }

        $user = $this->auth->user();

        if (($restriction = $coupon->restrictionFor($user instanceof User ? $user : null)) !== null) {
            throw CartException::invalidCoupon($restriction);
        }

        $merchandise = $this->items()
            ->reject(fn (CartItem $item): bool => $item->variant->product->is_gift_card)
            ->sum(fn (CartItem $item): int => $item->variant->price() * $item->quantity);

        if (! $coupon->meetsMinimum($merchandise)) {
            throw CartException::invalidCoupon(sprintf(
                'Ce code est valable à partir de %s d\'achats.',
                Money::format((int) $coupon->min_subtotal),
            ));
        }

        $cart->update(['coupon_id' => $coupon->id]);
        $cart->setRelation('coupon', $coupon);

        return $coupon;
    }

    public function removeCoupon(): void
    {
        $this->find()?->update(['coupon_id' => null]);
    }

    public function applyGiftCard(string $code): GiftCard
    {
        $cart = $this->find();
        $giftCard = GiftCard::query()->where('code', GiftCard::normalizeCode($code))->first();

        if (! $cart instanceof Cart || $giftCard === null || ! $giftCard->isUsable()) {
            throw CartException::invalidGiftCard();
        }

        $cart->update(['gift_card_id' => $giftCard->id]);
        $cart->setRelation('giftCard', $giftCard);

        return $giftCard;
    }

    public function removeGiftCard(): void
    {
        $this->find()?->update(['gift_card_id' => null]);
    }

    public function clear(): void
    {
        $this->find()?->delete();
        $this->cart = null;
        $this->session->forget(self::SESSION_KEY);
    }

    /**
     * Move the guest cart into the user's cart after login, summing quantities.
     */
    public function mergeGuestCartInto(User $user): void
    {
        $token = $this->session->pull(self::SESSION_KEY);

        $guestCart = is_string($token)
            ? Cart::query()->where('token', $token)->whereNull('user_id')->with('items.variant')->first()
            : null;

        if ($guestCart === null) {
            return;
        }

        $this->cart = null;
        $userCart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

        foreach ($guestCart->items as $guestItem) {
            $item = $userCart->items()->firstOrNew(['product_variant_id' => $guestItem->product_variant_id], ['quantity' => 0]);
            $item->quantity = $this->clampedQuantity($guestItem->variant, $item->quantity + $guestItem->quantity, fail: false);
            $item->save();
        }

        $userCart->coupon_id ??= $guestCart->coupon_id;
        $userCart->gift_card_id ??= $guestCart->gift_card_id;
        $userCart->save();

        $guestCart->delete();
    }

    /**
     * Cap a requested quantity to the line maximum and to what can be ordered (stock plus pre-orders).
     *
     * @return int<1, max>
     */
    private function clampedQuantity(ProductVariant $variant, int $requested, bool $fail = true): int
    {
        $available = $variant->availableQuantity();
        $allowed = min(self::MAX_QUANTITY_PER_LINE, $available);

        if ($requested > $allowed && $fail) {
            throw $available >= self::MAX_QUANTITY_PER_LINE
                ? CartException::lineLimit(self::MAX_QUANTITY_PER_LINE)
                : CartException::insufficientStock($available);
        }

        return max(1, min($requested, $allowed));
    }
}
