# 4. Payment gateway abstraction; the webhook confirms payments

- Status: accepted

## Context

Stripe Checkout redirects the customer back to the shop after paying, but the redirect can be skipped (closed tab,
network error) or forged. The test suite must not depend on Stripe.

## Decision

Everything the shop needs from the provider goes through the `CheckoutGateway` interface: create, retrieve and expire
a session, refund a payment. `StripeCheckoutGateway` is bound in `AppServiceProvider`; tests bind a fake.

An order becomes `paid` only from data fetched from Stripe:

- the `checkout.session.completed` webhook, whose signature is checked by Cashier's middleware, and whose amount must
  match the order total;
- as a fallback, when the webhook has not arrived yet, the success page fetches the session from the Stripe API and
  checks its payment status and amount; reaching the page proves nothing on its own.

## Consequences

- Payment confirmation does not depend on the customer's browser.
- Switching or adding a provider means writing one class; actions and components are untouched.
- The real Stripe integration is covered by manual testing with the Stripe CLI, not by the automated suite.
