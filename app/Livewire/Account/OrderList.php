<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.shop')]
#[Title('Mes commandes')]
final class OrderList extends Component
{
    use WithPagination;

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();

        /** @var LengthAwarePaginator<int, Order> $orders */
        $orders = $user->orders()->withCount('items')->latest()->paginate(10);

        return view('livewire.account.order-list', ['orders' => $orders]);
    }
}
