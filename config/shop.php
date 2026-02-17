<?php

return [

    'currency' => 'EUR',

    /*
    | Amounts are expressed in cents.
    */
    'shipping' => [
        'flat_rate' => 490,
        'free_threshold' => 10000,
    ],

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

    'low_stock_threshold' => 3,

    'per_page' => 12,

];
