<x-legal.page title="Confidentialité" intro="Ce que nous collectons, pourquoi, et combien de temps.">
    <section>
        <h2>Données collectées</h2>
        <p>Nom, e-mail et mot de passe à la création du compte. Adresses de livraison et historique de commandes pour traiter vos achats. Aucune donnée bancaire n'est conservée par 4shion.</p>
    </section>
    <section>
        <h2>Utilisation</h2>
        <p>Vos données servent uniquement à gérer votre compte, expédier vos commandes et vous envoyer les e-mails qui s'y rapportent. Elles ne sont ni vendues ni cédées.</p>
    </section>
    <section>
        <h2>Vos droits</h2>
        <p>Vous pouvez consulter, corriger ou supprimer vos données depuis votre <a href="{{ route('profile') }}">profil</a>, ou en écrivant à <a href="mailto:{{ config('shop.company.email') }}">{{ config('shop.company.email') }}</a>.</p>
    </section>
    <section>
        <h2>Cookies</h2>
        <p>Seuls les cookies nécessaires au fonctionnement du site sont utilisés : session, panier et sécurité. Aucun traceur publicitaire.</p>
    </section>
</x-legal.page>
