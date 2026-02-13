@props(['value'])

<label {{ $attributes->merge(['class' => 'mb-2 block text-xs text-muted']) }}>
    {{ $value ?? $slot }}
</label>
