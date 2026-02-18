<x-layouts.bare title="Page introuvable" code="404" heading="Cette page n'existe pas." message="Le lien est peut-être ancien, ou le produit n'est plus au catalogue.">
    <x-slot:actions>
        <a href="{{ route('shop.index') }}" class="btn-outline">Voir la boutique</a>
    </x-slot:actions>
</x-layouts.bare>
