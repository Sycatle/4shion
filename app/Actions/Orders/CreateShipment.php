<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\Carrier;
use App\Enums\OrderStatus;
use App\Events\OrderShipped;
use App\Mail\ShipmentNotice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

final class CreateShipment
{
    /**
     * Hand part of a paid order to a carrier. The order becomes shipped once every unit that can
     * leave has left and no pre-order is waiting; until then the customer gets one e-mail per parcel.
     *
     * @param  array<int, int>  $quantities  units per order item id
     *
     * @throws InvalidArgumentException when the order is not shippable or a quantity exceeds what is left to ship
     */
    public function __invoke(Order $order, array $quantities, ?Carrier $carrier = null, ?string $trackingNumber = null): Shipment
    {
        $quantities = array_filter($quantities, fn (int $quantity): bool => $quantity > 0);

        if ($quantities === []) {
            throw new InvalidArgumentException('Aucun article à expédier.');
        }

        [$shipment, $complete, $order] = DB::transaction(function () use ($order, $quantities, $carrier, $trackingNumber): array {
            $order = Order::query()->lockForUpdate()->with('items')->findOrFail($order->id);

            if ($order->status !== OrderStatus::Paid) {
                throw new InvalidArgumentException("La commande {$order->number} ne peut pas être expédiée.");
            }

            foreach ($quantities as $itemId => $quantity) {
                $item = $order->items->firstWhere('id', $itemId);

                if (! $item instanceof OrderItem || $quantity > $item->shippableQuantity()) {
                    throw new InvalidArgumentException("Quantité à expédier trop élevée pour l’article {$itemId}.");
                }
            }

            $shipment = $order->shipments()->create([
                'carrier' => $carrier,
                'tracking_number' => $trackingNumber !== null && trim($trackingNumber) !== '' ? trim($trackingNumber) : null,
                'shipped_at' => now(),
            ]);

            foreach ($quantities as $itemId => $quantity) {
                $shipment->items()->create(['order_item_id' => $itemId, 'quantity' => $quantity]);
            }

            $complete = ! $order->hasBackorders()
                && $order->items->every(fn (OrderItem $item): bool => $item->shippableQuantity() === 0);

            if ($complete) {
                $order->transitionTo(OrderStatus::Shipped, ['shipped_at' => now()]);
            }

            return [$shipment, $complete, $order];
        });

        if ($complete) {
            event(new OrderShipped($order->refresh()));
        } else {
            Mail::to($order->user)->send(new ShipmentNotice($shipment));
        }

        return $shipment;
    }
}
