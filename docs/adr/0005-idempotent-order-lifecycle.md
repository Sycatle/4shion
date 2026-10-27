# 5. Idempotent order lifecycle

- Status: accepted

## Context

The same transition can be requested several times or concurrently: Stripe retries webhooks, the success page polls
while the webhook arrives, an admin refunds while `charge.refunded` is being delivered.

## Decision

- `OrderStatus` defines the allowed transitions; `Order::transitionTo()` refuses any other move.
- Each lifecycle action locks the order row, checks `canTransitionTo()` and returns `false` when the move no longer
  applies, so a repeated call is a no-op rather than an error. Side effects (events, emails, coupon usage) happen
  only when the transition actually occurred.
- Stripe events are recorded in `stripe_events` by id with `insertOrIgnore`; a redelivered event is dropped before
  reaching the queue. Processing runs in the `ProcessStripeEvent` job with retries and backoff.
- Calls to Stripe that create resources use idempotency keys derived from the order.

## Consequences

- Duplicate or out-of-order deliveries cannot double-count a coupon, send two emails or restock twice.
- The webhook endpoint answers quickly; transient failures are retried by the worker.
- Adding a status means updating the transition table, which the unit tests pin down.
