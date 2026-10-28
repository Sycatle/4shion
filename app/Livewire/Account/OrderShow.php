<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Payments\CheckoutGateway;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.shop')]
final class OrderShow extends Component
{
    public Order $order;

    public ?string $error = null;

    public function mount(Order $order): void
    {
        Gate::authorize('view', $order);

        $this->order = $order->load('items.giftCards', 'shipments', 'returns');
    }

    public function pay(CheckoutGateway $gateway): void
    {
        Gate::authorize('view', $this->order);

        $this->error = null;

        if ($this->order->status !== OrderStatus::Pending) {
            return;
        }

        try {
            $session = $gateway->createSession($this->order);
        } catch (Throwable $e) {
            report($e);
            $this->error = 'Le paiement est momentanément indisponible. Veuillez réessayer dans quelques instants.';

            return;
        }

        $this->order->update(['stripe_session_id' => $session->id]);

        $this->redirect((string) $session->url);
    }

    public function render(): View
    {
        return view('livewire.account.order-show')->title("Commande {$this->order->number}");
    }
}
