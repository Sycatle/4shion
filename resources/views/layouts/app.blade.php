<x-layouts.shop>
    <div class="shell py-10">
        @isset($header)
            <h1 class="mb-8 text-3xl sm:text-4xl">{{ $header }}</h1>
        @endisset

        {{ $slot }}
    </div>
</x-layouts.shop>
