<?php

namespace App\Livewire\Account;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.shop')]
class OrderShow extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        Gate::authorize('view', $order);

        $this->order = $order->load('items');
    }

    public function render(): View
    {
        return view('livewire.account.order-show')->title("Commande {$this->order->number}");
    }
}
