<button type="button" x-on:click="$dispatch('open-cart')" class="relative p-1" aria-label="Panier ({{ $count }})">
    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M5 8h14l-1 12H6L5 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2" stroke-linecap="round"/></svg>
    @if ($count > 0)
        <span class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center bg-signal px-1 text-[10px] font-semibold text-white">{{ $count }}</span>
    @endif
</button>
