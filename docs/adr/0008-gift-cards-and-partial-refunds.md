# 8. Gift cards as a tender, partial refunds through returns

- Status: accepted

## Context

A gift card can pay for an order in full or in part, so an order may be settled by two tenders: the card balance and
Stripe. Returns introduced refunds for part of an order, while the order lifecycle (ADR 5) only knows a full
`Refunded` state.

## Decision

- The gift card amount is computed with the cart totals and stored on the order (`gift_card_total`). The balance is
  debited inside `CreateOrder`, under a row lock, through a `gift_card_redemptions` row. Stripe is only asked for
  `Order::amountDue()`, the total minus the gift card part. An order fully covered by a gift card never reaches Stripe.
- Every path that undoes an order (cancel, expiry, full refund) goes through `RestoreOrderResources`. It reverses the
  redemptions once (`reversed_at`) and deactivates gift cards that the order itself had bought.
- A partial refund is a property of a return, not an order status. `RefundReturn` refunds an amount up to the card
  payment not yet refunded and accumulates it on `orders.refunded_total`. It writes that total before calling the
  provider, so the `charge.refunded` webhook can tell a partial refund from a full one. A PDF credit note is issued
  for the return.

## Consequences

- The order state machine stays small. Partial money movements are tracked in amounts, not in states.
- The card can never be refunded more than it was charged, even across several returns.
- Known limit: the gift card part of a partially returned order is not re-credited (see the roadmap).
