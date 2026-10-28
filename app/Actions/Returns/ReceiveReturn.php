<?php

declare(strict_types=1);

namespace App\Actions\Returns;

use App\Enums\ReturnStatus;
use App\Enums\StockMovementReason;
use App\Inventory\StockLedger;
use App\Mail\ReturnUpdate;
use App\Models\OrderReturn;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

final readonly class ReceiveReturn
{
    public function __construct(private StockLedger $ledger) {}

    /**
     * The parcel arrived. Units in good condition go back on the shelf.
     *
     * @throws InvalidArgumentException when the return was not approved
     */
    public function __invoke(OrderReturn $return, bool $restock, ?User $by = null): void
    {
        $return = DB::transaction(function () use ($return, $restock, $by): OrderReturn {
            $return = OrderReturn::query()->lockForUpdate()->with('order.user')->findOrFail($return->id);
            $return->transitionTo(ReturnStatus::Received, ['received_at' => now()]);

            if ($restock) {
                foreach ($return->items()->with('orderItem.variant')->get() as $line) {
                    $variant = $line->orderItem->variant;

                    if ($variant !== null) {
                        $this->ledger->add($variant, $line->quantity, StockMovementReason::Refund, $return->order, $by, "Retour {$return->number}");
                    }
                }
            }

            return $return;
        });

        Mail::to($return->order->user)->send(new ReturnUpdate($return));
    }
}
