<?php

declare(strict_types=1);

namespace App\Actions\Returns;

use App\Enums\ReturnReason;
use App\Enums\ReturnStatus;
use App\Mail\ReturnUpdate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

final class RequestReturn
{
    /**
     * @param  array<int, int>  $quantities  units to send back per order item id
     *
     * @throws InvalidArgumentException when the order is out of the return window or a quantity is too high
     */
    public function __invoke(Order $order, array $quantities, ReturnReason $reason, ?string $comment = null): OrderReturn
    {
        $quantities = array_filter($quantities, fn (int $quantity): bool => $quantity > 0);

        if ($quantities === []) {
            throw new InvalidArgumentException('Choisissez au moins un article à retourner.');
        }

        $return = DB::transaction(function () use ($order, $quantities, $reason, $comment): OrderReturn {
            $order = Order::query()->lockForUpdate()->with('items')->findOrFail($order->id);

            if (! $order->isReturnable()) {
                throw new InvalidArgumentException('Cette commande n’est plus éligible au retour.');
            }

            foreach ($quantities as $itemId => $quantity) {
                $item = $order->items->firstWhere('id', $itemId);

                if (! $item instanceof OrderItem || $quantity > $item->returnableQuantity()) {
                    throw new InvalidArgumentException('Quantité à retourner trop élevée pour un des articles.');
                }
            }

            $return = $order->returns()->create([
                'number' => 'PENDING-'.bin2hex(random_bytes(8)),
                'status' => ReturnStatus::Requested,
                'reason' => $reason,
                'comment' => $comment !== null && trim($comment) !== '' ? trim($comment) : null,
            ]);
            $return->update(['number' => OrderReturn::numberFor($return->id)]);

            foreach ($quantities as $itemId => $quantity) {
                $return->items()->create(['order_item_id' => $itemId, 'quantity' => $quantity]);
            }

            return $return;
        });

        Mail::to($return->order->user)->send(new ReturnUpdate($return));

        return $return;
    }
}
