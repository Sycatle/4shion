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

    'low_stock_threshold' => 3,

    'per_page' => 12,

];
