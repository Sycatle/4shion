<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Cart\CartManager;
use App\Cart\CartTotals;
use App\Data\ShippingAddress;
use App\Enums\OrderStatus;
use App\Exceptions\Cart\CartException;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateOrder
{
    public function __construct(private CartManager $cart) {}

    /**
     * Turn the current cart into a pending order and reserve its stock.
     * Variants are locked so that concurrent checkouts cannot oversell.
     *
     * @throws CartException when the cart is empty or an item is no longer available
     */
    public function __invoke(User $user, ShippingAddress $address): Order
    {
        return DB::transaction(function () use ($user, $address): Order {
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

                if (! $variant->product->is_active || $variant->stock < $item->quantity) {
                    throw CartException::unavailable($variant->product->name, $variant->product->is_active ? $variant->stock : 0);
                }
            }

            $coupon = $this->cart->coupon();
            $totals = CartTotals::calculate($items, $coupon);
            $vatRate = Config::float('shop.vat_rate');

            $order = Order::create([
                'number' => 'PENDING-'.Str::uuid(),
                'user_id' => $user->id,
                'status' => OrderStatus::Pending,
                'subtotal' => $totals->subtotal,
                'discount_total' => $totals->discount,
                'shipping_total' => $totals->shipping,
                'total' => $totals->total,
                'vat_rate' => $vatRate,
                'vat_total' => Order::vatIncludedIn($totals->total, $vatRate),
                'currency' => Config::string('shop.currency'),
                'coupon_id' => $totals->discount > 0 ? $coupon?->id : null,
                'coupon_code' => $totals->discount > 0 ? $coupon?->code : null,
                'shipping_address' => $address,
                'billing_address' => $address,
            ]);

            $order->update(['number' => Order::numberFor($order->id)]);

            foreach ($items as $item) {
                $variant = $this->lockedVariant($variants, $item);

                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_label' => $variant->label(),
                    'sku' => $variant->sku,
                    'unit_price' => $variant->price(),
                    'quantity' => $item->quantity,
                    'total' => $variant->price() * $item->quantity,
                ]);

                $variant->decrement('stock', $item->quantity);
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
