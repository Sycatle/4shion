# Backlog

## Doing
Rien. Les lots 1 à 6 sont codés, testés et vérifiés dans le navigateur ; rien n'est commité (tout est sur la branche `feat/stock-promos`, à découper avant PR).

## Todo (hors périmètre livré)
- Lot 2 : photos dans les avis, vidéo produit, zoom/galerie.
- Lot 3 : API transporteur (Mondial Relay points relais, étiquettes Colissimo) — besoin des clés sandbox ; aujourd'hui suivi par lien public (`App\Enums\Carrier`).
- Lot 3 : remboursement d'une commande payée en partie par carte cadeau (la part carte cadeau n'est pas recréditée).
- Lot 4 : taux de conversion (aucune donnée de visites) ; import CSV limité au catalogue.
- Lot 6 : i18n FR/EN limitée à l'en-tête, au pied de page, à la bannière cookies, à la newsletter et à la fiche produit (`lang/en.json`) ; panier, checkout, compte, e-mails et admin restent en français.
- Lot 6 : PayPal / Klarna / Apple Pay s'activent côté tableau de bord Stripe (voir `STRIPE_PAYMENT_METHODS`), pas testés contre Stripe.

## Done
- Installation de Laravel Boost (guidelines + skills)
- Lot 1 : stock (journal, seuils, alertes, précommande), coupons avancés, soldes, promotions automatiques, cartes cadeaux
- Lot 2 : favoris partageables, récemment consultés, recommandations, avis (votes utiles, réponse marchand, note sur les cartes), relance panier abandonné, commande invité
- Lot 4 : KPI + période, graphiques, rôles (admin / préparateur / support), journal d'activité, exports CSV, import catalogue, bons de préparation, expédition groupée, notes internes, fiche client
- Lot 3 : modes de livraison, colis et expéditions partielles, suivi, retours (RMA) avec remboursement partiel et avoir PDF
- Lot 5 : sitemap, robots, Open Graph / canonical / JSON-LD, pages catégorie, journal, newsletter double opt-in, bannière cookies, export et suppression des données, flux Google Merchant
- Lot 6 : 2FA obligatoire pour l'admin (`ADMIN_MFA_REQUIRED`), limitation de débit, alerte de nouvelle connexion, moyens de paiement Stripe configurables, bascule FR/EN
