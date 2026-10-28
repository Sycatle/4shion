<x-layouts.shop :title="$heading" :noindex="true">
    <div class="shell py-16">
        <h1 class="display text-6xl sm:text-7xl">{{ $heading }}</h1>
        <p class="mt-6 max-w-lg text-sm">{{ $message }}</p>
        <a href="{{ route('home') }}" wire:navigate class="btn-primary mt-8">Retour à la boutique</a>
    </div>
</x-layouts.shop>
