<?php

declare(strict_types=1);

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
    'vat_rate' => 20.0,

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

    /*
    | Abandoned cart reminder: sent once, after `after_hours` of inactivity and no later than
    | `max_days`. `coupon` is an optional code quoted in the e-mail.
    */
    'abandoned_cart' => [
        'after_hours' => 24,
        'max_days' => 7,
        'coupon' => env('ABANDONED_CART_COUPON'),
    ],

    /*
    | Customers can request a return within this many days of receiving the parcel. The legal
    | withdrawal right is 14 days; the shop grants more.
    */
    'returns' => [
        'window_days' => 30,
    ],

    /*
    | Staff must set up an authenticator app (TOTP) to use the back-office.
    */
    'admin_mfa_required' => filter_var(env('ADMIN_MFA_REQUIRED', true), FILTER_VALIDATE_BOOL),

    /*
    | Payment methods offered on the Stripe payment page, comma-separated (e.g. "card,paypal,klarna").
    | Empty: Stripe shows every method enabled in the dashboard, which adds Apple Pay / Google Pay
    | to cards automatically.
    */
    'payment_methods' => array_values(array_filter(array_map(trim(...), explode(',', (string) env('STRIPE_PAYMENT_METHODS', ''))))),

    'low_stock_threshold' => 3,

    'per_page' => 12,

];
