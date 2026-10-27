<?php

declare(strict_types=1);

namespace App\Livewire\Shop;

use App\Actions\Orders\MarkOrderPaid;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Payments\CheckoutGateway;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.shop')]
#[Title('Confirmation de commande')]
final class CheckoutSuccess extends Component
{
    #[Url(as: 'session_id')]
    public string $sessionId = '';

    public Order $order;

    public function mount(): void
    {
        $this->order = Order::query()
            ->where('user_id', auth()->id())
            ->where('stripe_session_id', $this->sessionId)
            ->with('items')
            ->firstOrFail();
    }

    /**
     * The webhook is the source of truth, but if it has not arrived yet we ask the
     * payment provider directly so the customer is not left waiting.
     */
    public function refreshStatus(CheckoutGateway $gateway, MarkOrderPaid $markOrderPaid): void
    {
        if ($this->order->status === OrderStatus::Pending) {
            try {
                $session = $gateway->retrieveSession($this->sessionId);

                if ($session->paid && $session->amountTotal === $this->order->total) {
                    $markOrderPaid($this->order, $session->paymentIntent);
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        $this->order->refresh();
    }

    public function render(): View
    {
        return view('livewire.shop.checkout-success');
    }
}
