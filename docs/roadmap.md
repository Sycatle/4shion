# Roadmap

Known gaps and next steps, roughly in priority order.

## Next

- **Carrier integrations**: Mondial Relay pick-up points and Colissimo labels through their APIs. Today staff enter
  the tracking number by hand and customers follow a public tracking link (`App\Enums\Carrier`). Blocked on sandbox
  credentials.
- **Gift card part of partial refunds**: `RefundReturn` refunds the card payment only, so the gift card share of a
  returned order is not re-credited. Full refunds and cancellations already restore it (ADR 8).
- **Full English translation**: the header, footer, cookie banner, newsletter and product page are translated
  (`lang/en.json`). Cart, checkout, account, e-mails and the back office are still French only.

## Later

- Customer photos in reviews, product video, image zoom in the gallery.
- Conversion rate on the dashboard. It needs visit data, which the shop does not collect today.
- CSV import for entities other than the catalogue (customers, stock counts).
- PayPal, Klarna and Apple Pay end-to-end checks against Stripe. They are enabled through `STRIPE_PAYMENT_METHODS`
  and the Stripe dashboard, but they are only exercised through the fake gateway in tests.
