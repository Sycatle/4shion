<x-legal.page title="Contact" intro="Une question sur une commande, une taille ou un retour ? Nous répondons sous 48 heures ouvrées.">
    <section>
        <h2>Par e-mail</h2>
        <p><a href="mailto:{{ config('shop.company.email') }}">{{ config('shop.company.email') }}</a></p>
        <p>Indiquez votre numéro de commande si votre demande en concerne une.</p>
    </section>
    <section>
        <h2>Adresse</h2>
        <p>{{ config('shop.company.name') }}<br>{{ config('shop.company.address') }}<br>{{ config('shop.company.city') }}</p>
    </section>
    <section>
        <h2>Livraison et retours</h2>
        <p>Livraison en {{ config('shop.shipping.delay') }}, retours gratuits sous 30 jours. Le détail est dans les <a href="{{ route('legal.terms') }}">conditions de vente</a>.</p>
    </section>
</x-legal.page>
