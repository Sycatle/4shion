<x-legal.page title="Mentions légales">
    <section>
        <h2>Éditeur</h2>
        <p>{{ config('shop.company.name') }}<br>{{ config('shop.company.address') }}, {{ config('shop.company.city') }}<br>SIRET {{ config('shop.company.siret') }}<br>TVA intracommunautaire {{ config('shop.company.vat_number') }}</p>
        <p>Contact : <a href="mailto:{{ config('shop.company.email') }}">{{ config('shop.company.email') }}</a></p>
    </section>
    <section>
        <h2>Hébergement</h2>
        <p>Ce site est un projet de démonstration. Les coordonnées de la société et de l'hébergeur sont fictives.</p>
    </section>
</x-legal.page>
