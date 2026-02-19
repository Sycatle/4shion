@props(['status'])

@php
    use App\Enums\OrderStatus;

    $steps = ['Commandée', 'Payée', 'Expédiée', 'Livrée'];
    $reached = match ($status) {
        OrderStatus::Pending => 1,
        OrderStatus::Paid => 2,
        OrderStatus::Shipped => 3,
        OrderStatus::Delivered => 4,
        default => 0,
    };
@endphp

@if ($reached > 0)
    <ol class="grid grid-cols-4 gap-2 text-sm" aria-label="Avancement de la commande">
        @foreach ($steps as $index => $label)
            <li @if ($index + 1 === $reached) aria-current="step" @endif>
                <span @class(['block h-1', 'bg-signal' => $index < $reached, 'bg-concrete' => $index >= $reached])></span>
                <span @class(['mt-2 block', 'font-semibold' => $index + 1 === $reached, 'text-muted' => $index >= $reached])>{{ $label }}</span>
            </li>
        @endforeach
    </ol>
@endif
