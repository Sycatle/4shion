<?php

declare(strict_types=1);

namespace App\Actions\Returns;

use App\Enums\ReturnStatus;
use App\Mail\ReturnUpdate;
use App\Models\OrderReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

final class RejectReturn
{
    /** @throws InvalidArgumentException when the return can no longer be rejected */
    public function __invoke(OrderReturn $return, ?string $note = null): void
    {
        $return = DB::transaction(function () use ($return, $note): OrderReturn {
            $return = OrderReturn::query()->lockForUpdate()->with('order.user')->findOrFail($return->id);
            $return->transitionTo(ReturnStatus::Rejected, ['decision_note' => $note !== null && trim($note) !== '' ? trim($note) : null]);

            return $return;
        });

        Mail::to($return->order->user)->send(new ReturnUpdate($return));
    }
}
