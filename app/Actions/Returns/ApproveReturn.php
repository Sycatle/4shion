<?php

declare(strict_types=1);

namespace App\Actions\Returns;

use App\Enums\ReturnStatus;
use App\Mail\ReturnUpdate;
use App\Models\OrderReturn;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

final class ApproveReturn
{
    /** @throws InvalidArgumentException when the return is not waiting for a decision */
    public function __invoke(OrderReturn $return): void
    {
        $return = DB::transaction(function () use ($return): OrderReturn {
            $return = OrderReturn::query()->lockForUpdate()->with('order.user')->findOrFail($return->id);
            $return->transitionTo(ReturnStatus::Approved);

            return $return;
        });

        Mail::to($return->order->user)->send(new ReturnUpdate($return));
    }
}
