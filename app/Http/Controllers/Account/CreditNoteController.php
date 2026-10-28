<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Returns\GenerateCreditNote;
use App\Enums\ReturnStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturn;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class CreditNoteController extends Controller
{
    public function __invoke(Order $order, OrderReturn $return, GenerateCreditNote $generate): Response
    {
        Gate::authorize('view', $order);
        abort_unless($return->order_id === $order->id && $return->status === ReturnStatus::Refunded, 404);

        return $generate($return)->download(GenerateCreditNote::filename($return));
    }
}
