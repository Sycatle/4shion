# 4shion

A clothing e-commerce site built with Laravel, written as a showcase of current Laravel practice. The storefront UI is in French; the code, commits and documentation are in English.

## Features

- Catalogue with categories, product variants (size and colour, stock per variant), full-text search (Scout and Meilisearch) and filters stored in the URL
- Persistent cart, merged on login, with coupon codes and a free-shipping progress bar
- Checkout through Stripe Checkout, with signed webhooks and an idempotent `MarkOrderPaid` action
- Customer account: orders with a status timeline, PDF invoices, address book, moderated product reviews
- Filament admin panel: products, categories, coupons, orders (ship, refund, invoice), review moderation and dashboard widgets
- Queued order confirmation and shipping emails

## Stack

Laravel 13, PHP 8.5 (Sail), Livewire 4, Alpine, Tailwind 3, Vite, Filament 5, Cashier, Scout, spatie/laravel-medialibrary, PostgreSQL, Redis, Meilisearch, Mailpit. Tests run on Pest, static analysis on Larastan (level 6), formatting on Pint.

## Getting started

Everything runs through Laravel Sail; PHP, Composer and Node are not needed on the host.

```bash
cp .env.example .env
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html composer:2 composer install --ignore-platform-reqs
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan storage:link
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan scout:sync-index-settings
./vendor/bin/sail artisan scout:import "App\Models\Product"
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

| Service | URL |
| --- | --- |
| Storefront | http://localhost |
| Admin panel | http://localhost/admin |
| Mailpit | http://localhost:8025 |

Emails are queued: the `queue` Sail service runs the worker, so confirmation emails appear in Mailpit shortly after a payment.

Seeded accounts (password `password`):

- Customer: `client@4shion.test`
- Admin: `admin@4shion.test`

Seeded coupons: `BIENVENUE10`, `PREMIUM20` and an expired `EXPIRE`.

During frontend work, `./vendor/bin/sail npm run dev` serves assets with hot reload.

## Payments

Add your Stripe test keys to `.env` (`STRIPE_KEY`, `STRIPE_SECRET`), then forward webhooks to the app:

```bash
stripe listen --forward-to localhost/stripe/webhook
```

Copy the printed signing secret into `STRIPE_WEBHOOK_SECRET`. An order stays `pending` until the webhook (or, as a fallback, the success page) confirms the payment. The test suite never calls Stripe: it swaps the `CheckoutGateway` for a fake.

## Quality checks

```bash
./vendor/bin/sail composer lint      # Pint
./vendor/bin/sail composer analyse   # Larastan
./vendor/bin/sail pest               # tests
```

The same three checks run in GitHub Actions (`.github/workflows/ci.yml`).

## Project layout

- `app/Actions` holds the business operations (checkout, order lifecycle, catalogue queries), kept out of controllers and components.
- `app/Payments` abstracts the payment provider behind `CheckoutGateway`.
- `app/Livewire` holds the storefront and account components; `app/Filament` the admin resources.
- `config/shop.php` holds shipping, VAT, the fictional company identity printed on invoices, the colour field of each category and the size guide.
- Amounts are integers in cents everywhere; `App\Support\Money` formats them.

## Design

Display type is Archivo (condensed), body text is Inter. Each category owns a colour field set in `config/shop.php`; product packshots are blended onto it. The `4` emblem is traced from the Archivo glyph (`resources/views/components/shop/emblem.blade.php`).
