@props(['status'])

@php
    $classes = match ($status->color()) {
        'success' => 'bg-emerald-50 text-emerald-800',
        'info' => 'bg-sky-50 text-sky-800',
        'danger' => 'bg-red-50 text-red-800',
        default => 'bg-amber-50 text-amber-800',
    };
@endphp

<span {{ $attributes->class(['inline-block px-2.5 py-1 text-[11px] font-medium uppercase tracking-[0.1em]', $classes]) }}>{{ $status->label() }}</span>
