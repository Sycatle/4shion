@props(['status'])

@php
    $classes = match ($status->color()) {
        'success' => 'bg-emerald-100 text-emerald-900',
        'info' => 'bg-signal/10 text-signal-dark',
        'danger' => 'bg-red-100 text-red-900',
        default => 'bg-amber-100 text-amber-900',
    };
@endphp

<span {{ $attributes->class(['inline-block px-2.5 py-1 text-xs font-semibold', $classes]) }}>{{ $status->label() }}</span>
