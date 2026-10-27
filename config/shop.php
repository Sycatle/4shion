<?php

return [

    'currency' => 'EUR',

    /*
    | Amounts are expressed in cents.
    */
    'shipping' => [
        'flat_rate' => 490,
        'free_threshold' => 10000,
        'delay' => '2 à 4 jours ouvrés',
    ],

    /*
    | Pending orders older than this are cancelled and their stock released. Must exceed
    | the payment session lifetime (30 minutes) so a customer still paying is never cut off.
    */
    'pending_order_ttl_minutes' => 60,

    /*
    | Prices are stored tax-inclusive; the rate is only used to display VAT on invoices.
    */
    'vat_rate' => 20,

    /*
    | Seller identity printed on invoices (fictional demo values).
    */
    'company' => [
        'name' => '4shion SAS',
        'address' => '24 rue du Faubourg Saint-Honoré',
        'city' => '75008 Paris',
        'siret' => '123 456 789 00012',
        'vat_number' => 'FR12 123456789',
        'email' => 'hello@4shion.test',
    ],

    /*
    | Each category owns a colour field; product images are blended onto it.
    | `ink` is the text colour that stays legible on the field.
    */
    'category_themes' => [
        'sweats-hoodies' => ['field' => '#9FB3C8', 'ink' => '#17181A'],
        't-shirts' => ['field' => '#E3C65A', 'ink' => '#17181A'],
        'vestes-manteaux' => ['field' => '#2F4A3C', 'ink' => '#F7F7F4'],
        'pantalons' => ['field' => '#6B5B4B', 'ink' => '#F7F7F4'],
        'accessoires' => ['field' => '#E6C1BE', 'ink' => '#17181A'],
    ],

    'default_category_theme' => ['field' => '#E4E4E0', 'ink' => '#17181A'],

    /*
    | Garment measurements in centimetres, flat. Used by the size guide on product pages.
    */
    'size_guide' => [
        'columns' => ['Taille', 'Poitrine', 'Longueur', 'Épaules'],
        'rows' => [
            ['XS', '98', '66', '42'],
            ['S', '103', '68', '44'],
            ['M', '108', '70', '46'],
            ['L', '113', '72', '48'],
            ['XL', '118', '74', '50'],
        ],
    ],

    'low_stock_threshold' => 3,

    'per_page' => 12,

];
