# 6. Money in integer cents; VAT snapshot on the order

- Status: accepted

## Context

Floating-point amounts drift when summed. Prices are displayed tax-inclusive, as is the norm for French consumers,
and an invoice must keep showing the VAT that applied when it was issued.

## Decision

- Every amount is an integer number of cents, in the database, in PHP and in Stripe calls. `App\Support\Money`
  only formats.
- Percentage discounts round down to the cent; fixed discounts are capped at the subtotal.
- At order creation the VAT rate and the VAT contained in the total are stored on the order (`vat_rate`,
  `vat_total`). Invoices read those columns, never the configuration.

## Consequences

- Totals are exact and match what Stripe charges.
- Changing `shop.vat_rate` affects new orders only.
- A multi-rate catalogue (for example reduced-rate items) would need the rate per order line; the column layout
  leaves room for it.
