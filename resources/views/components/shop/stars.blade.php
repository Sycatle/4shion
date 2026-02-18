@props(['rating'])

<span {{ $attributes->class('inline-flex gap-0.5') }} role="img" aria-label="Note : {{ $rating }} sur 5">
    @foreach (range(1, 5) as $star)
        <svg class="h-4 w-4 {{ $star <= round($rating) ? 'text-ink' : 'text-line' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="m10 1.5 2.6 5.6 6.1.7-4.5 4.2 1.2 6L10 15l-5.4 3 1.2-6L1.3 7.8l6.1-.7L10 1.5Z"/></svg>
    @endforeach
</span>
