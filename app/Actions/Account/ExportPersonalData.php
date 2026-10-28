<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\Address;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\User;

final class ExportPersonalData
{
    /**
     * Everything the shop holds about a customer, in a portable structure (GDPR art. 20).
     *
     * @return array<string, mixed>
     */
    public function __invoke(User $user): array
    {
        $newsletter = NewsletterSubscriber::query()->where('email', $user->email)->first();

        return [
            'exported_at' => now()->toAtomString(),
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'registered_at' => $user->created_at?->toAtomString(),
            ],
            'addresses' => $user->addresses()->get()->map(fn (Address $address): array => $address->toArray())->all(),
            'orders' => $user->orders()->with('items', 'shipments', 'returns')->get()->map(fn (Order $order): array => [
                'number' => $order->number,
                'status' => $order->status->value,
                'placed_at' => $order->created_at?->toAtomString(),
                'total_cents' => $order->total,
                'shipping_address' => $order->shipping_address->toArray(),
                'items' => $order->items->map(fn (OrderItem $item): array => [
                    'product' => $item->product_name,
                    'variant' => $item->variant_label,
                    'quantity' => $item->quantity,
                    'total_cents' => $item->total,
                ])->all(),
            ])->all(),
            'reviews' => Review::query()->where('user_id', $user->id)->get()->map(fn (Review $review): array => [
                'product_id' => $review->product_id,
                'rating' => $review->rating,
                'title' => $review->title,
                'body' => $review->body,
            ])->all(),
            'wishlist' => $user->wishlist()->pluck('products.name')->all(),
            'newsletter' => $newsletter instanceof NewsletterSubscriber ? [
                'active' => $newsletter->isActive(),
                'confirmed_at' => $newsletter->confirmed_at?->toAtomString(),
            ] : null,
        ];
    }
}
