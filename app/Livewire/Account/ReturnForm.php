<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Actions\Returns\RequestReturn;
use App\Enums\ReturnReason;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.shop')]
#[Title('Retourner des articles')]
final class ReturnForm extends Component
{
    public Order $order;

    /** @var array<int, int|string> units to send back, by order item id */
    public array $quantities = [];

    public string $reason = '';

    public string $comment = '';

    public ?string $error = null;

    public function mount(Order $order): void
    {
        Gate::authorize('view', $order);
        abort_unless($order->isReturnable(), 404);

        $this->order = $order->load('items');
        $this->reason = ReturnReason::Withdrawal->value;
    }

    public function submit(RequestReturn $requestReturn): void
    {
        $this->error = null;
        $this->validate([
            'reason' => ['required', 'in:'.implode(',', array_column(ReturnReason::cases(), 'value'))],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $quantities = [];

        foreach ($this->quantities as $itemId => $quantity) {
            $quantities[(int) $itemId] = max(0, (int) $quantity);
        }

        try {
            $requestReturn($this->order, $quantities, ReturnReason::from($this->reason), $this->comment);
        } catch (InvalidArgumentException $e) {
            $this->error = $e->getMessage();

            return;
        }

        session()->flash('status', 'Votre demande de retour a été enregistrée. Vous recevrez un e-mail dès qu’elle sera traitée.');
        $this->redirectRoute('account.orders.show', $this->order, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.account.return-form', [
            'items' => $this->order->items->filter(fn (OrderItem $item): bool => $item->returnableQuantity() > 0),
            'deadline' => $this->order->returnDeadline(),
        ]);
    }
}
