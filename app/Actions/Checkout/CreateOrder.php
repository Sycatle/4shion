<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Cart\CartManager;
use App\Data\ShippingAddress;
use App\Enums\OrderStatus;
use App\Enums\StockMovementReason;
use App\Exceptions\Cart\CartException;
use App\Inventory\StockLedger;
use App\Models\CartItem;
use App\Models\GiftCard;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateOrder
{
    public function __construct(private CartManager $cart, private StockLedger $ledger) {}

    /**
     * Turn the current cart into a pending order, reserve its stock and debit the gift card.
     * Variants are locked so that concurrent checkouts cannot oversell. Units ordered beyond
     * the stock of a pre-order variant are recorded as backordered.
     *
     * @throws CartException when the cart is empty or an item is no longer available
     */
    public function __invoke(User $user, ShippingAddress $address, ?ShippingMethod $shippingMethod = null): Order
    {
        return DB::transaction(function () use ($user, $address, $shippingMethod): Order {
            $items = $this->cart->items();

            if ($items->isEmpty()) {
                throw CartException::emptyCart();
            }

            $variants = ProductVariant::query()
                ->whereIn('id', $items->pluck('product_variant_id'))
                ->with('product')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $variant = $this->lockedVariant($variants, $item);

                if (! $variant->product->is_active || $variant->availableQuantity() < $item->quantity) {
                    throw CartException::unavailable($variant->product->name, $variant->product->is_active ? min($variant->availableQuantity(), CartManager::MAX_QUANTITY_PER_LINE) : 0);
                }
            }

            $giftCard = $this->cart->giftCard();
            $giftCard = $giftCard instanceof GiftCard ? GiftCard::query()->lockForUpdate()->find($giftCard->id) : null;

            $totals = $this->cart->totalsFor($items, $giftCard, $shippingMethod);
            $vatRate = Config::float('shop.vat_rate');
            // Selling a gift card is not a taxable supply: VAT is due when it is spent.
            $giftCardSales = $items
                ->filter(fn (CartItem $item): bool => $item->variant->product->is_gift_card)
                ->sum(fn (CartItem $item): int => $item->variant->price() * $item->quantity);

            $order = Order::create([
                'number' => 'PENDING-'.Str::uuid(),
                'user_id' => $user->id,
                'status' => OrderStatus::Pending,
                'subtotal' => $totals->subtotal,
                'discount_total' => $totals->discount,
                'shipping_total' => $totals->shipping,
                'shipping_method_id' => $totals->shippingMethod?->id,
                'shipping_method_name' => $totals->shippingMethod?->name,
                'total' => $totals->total,
                'gift_card_total' => $totals->giftCard,
                'vat_rate' => $vatRate,
                'vat_total' => Order::vatIncludedIn($totals->total - $giftCardSales, $vatRate),
                'currency' => Config::string('shop.currency'),
                'coupon_id' => $totals->appliedCoupon?->id,
                'coupon_code' => $totals->appliedCoupon?->code,
                'promotion_id' => $totals->appliedPromotion?->id,
                'promotion_name' => $totals->appliedPromotion?->name,
                'shipping_address' => $address,
                'billing_address' => $address,
            ]);

            $order->update(['number' => Order::numberFor($order->id)]);

            foreach ($items as $item) {
                $variant = $this->lockedVariant($variants, $item);
                $isGiftCard = $variant->product->is_gift_card;
                $fromStock = $isGiftCard ? 0 : min($variant->stock, $item->quantity);

                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_label' => $variant->label(),
                    'sku' => $variant->sku,
                    'unit_price' => $variant->price(),
                    'quantity' => $item->quantity,
                    'backordered_quantity' => $isGiftCard ? 0 : $item->quantity - $fromStock,
                    'is_gift_card' => $isGiftCard,
                    'total' => $variant->price() * $item->quantity,
                ]);

                if ($fromStock > 0) {
                    $this->ledger->remove($variant, $fromStock, StockMovementReason::Sale, $order);
                }
            }

            if ($giftCard instanceof GiftCard && $totals->giftCard > 0) {
                $giftCard->decrement('balance', $totals->giftCard);
                $order->giftCardRedemptions()->create(['gift_card_id' => $giftCard->id, 'amount' => $totals->giftCard]);
            }

            $this->cart->clear();

            return $order->load('items');
        });
    }

    /**
     * @param  Collection<int, ProductVariant>  $variants  locked rows keyed by id
     */
    private function lockedVariant(Collection $variants, CartItem $item): ProductVariant
    {
        return $variants->get($item->product_variant_id)
            ?? throw CartException::unavailable($item->variant->product->name, 0);
    }
}
