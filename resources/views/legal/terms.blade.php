<x-legal.page title="Conditions de vente" intro="Dernière mise à jour : 1er février 2026. Site de démonstration, les informations de la société sont fictives.">
    <section>
        <h2>Commande</h2>
        <p>Toute commande passée sur 4shion suppose un compte client. Elle est confirmée par e-mail dès réception du paiement. Les prix sont indiqués en euros, toutes taxes comprises.</p>
    </section>
    <section>
        <h2>Paiement</h2>
        <p>Le paiement s'effectue par carte bancaire via Stripe. Aucune donnée bancaire n'est enregistrée sur nos serveurs. Le stock d'un article est réservé à la création de la commande.</p>
    </section>
    <section>
        <h2>Livraison</h2>
        <p>Livraison en France métropolitaine en {{ config('shop.shipping.delay') }}. Les frais sont de {{ \App\Support\Money::format(config('shop.shipping.flat_rate')) }}, offerts dès {{ \App\Support\Money::format(config('shop.shipping.free_threshold')) }} d'achat.</p>
    </section>
    <section>
        <h2>Retours et remboursement</h2>
        <p>Vous disposez de 30 jours après réception pour retourner un article neuf, dans son emballage d'origine. Le retour est gratuit. Le remboursement est effectué sur le moyen de paiement utilisé sous 14 jours.</p>
    </section>
    <section>
        <h2>Réclamations</h2>
        <p>Pour toute question sur une commande, écrivez-nous à <a href="mailto:{{ config('shop.company.email') }}">{{ config('shop.company.email') }}</a> en indiquant votre numéro de commande.</p>
    </section>
</x-legal.page>
