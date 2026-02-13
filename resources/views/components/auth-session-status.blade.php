@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'text-sm text-ink']) }}>
        {{ $status }}
    </div>
@endif
