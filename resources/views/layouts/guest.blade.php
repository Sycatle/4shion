<x-layouts.shop>
    <div class="grid min-h-[34rem] lg:grid-cols-2">
        <div class="relative hidden overflow-hidden bg-concrete lg:block" aria-hidden="true">
            <x-shop.emblem class="absolute -bottom-10 left-1/2 h-[90%] w-auto -translate-x-1/2 text-paper" />
        </div>
        <div class="flex items-center justify-center px-5 py-14 sm:px-8">
            <div class="w-full max-w-md">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.shop>
