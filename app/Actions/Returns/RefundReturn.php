<?php

declare(strict_types=1);

namespace App\Actions\Returns;

use App\Enums\ReturnStatus;
use App\Mail\ReturnUpdate;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Payments\CheckoutGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use LogicException;

final readonly class RefundReturn
{
    public function __construct(private CheckoutGateway $gateway) {}

    /**
     * Pay the customer back by card. Defaults to the value of the returned goods; the card can never
     * be refunded more than it was charged. Gift-card money is not re-credited.
     *
     * @throws LogicException when the order has no card payment to refund
     * @throws InvalidArgumentException when the amount is out of range or the return is not received
     */
    public function __invoke(OrderReturn $return, ?int $amount = null): void
    {
        $return = DB::transaction(function () use ($return, $amount): OrderReturn {
            $return = OrderReturn::query()->lockForUpdate()->with('order.user')->findOrFail($return->id);

            if (! $return->status->canTransitionTo(ReturnStatus::Refunded)) {
                throw new InvalidArgumentException("Le retour {$return->number} ne peut pas être remboursé.");
            }

            $order = Order::query()->lockForUpdate()->findOrFail($return->order_id);

            if ($order->stripe_payment_intent === null || $order->amountDue() === 0) {
                throw new LogicException("Order {$order->number} has no card payment to refund.");
            }

            $amount ??= $return->goodsValue();

            if ($amount <= 0 || $amount > $order->amountDue() - $order->refunded_total) {
                throw new InvalidArgumentException('Montant à rembourser invalide.');
            }

            // Recorded before the provider call so the webhook cannot mistake this for a full refund.
            $order->update(['refunded_total' => $order->refunded_total + $amount]);
            $this->gateway->refund($order->stripe_payment_intent, $amount, "return-{$return->id}");

            $return->transitionTo(ReturnStatus::Refunded, ['refund_amount' => $amount, 'refunded_at' => now()]);

            return $return;
        });

        Mail::to($return->order->user)->send(new ReturnUpdate($return));
    }
}
