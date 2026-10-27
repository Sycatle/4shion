# 3. Reserve stock when the order is created

- Status: accepted

## Context

Payment happens on Stripe's hosted page, minutes after the customer leaves the shop. Decrementing stock only once the
payment is confirmed lets two customers pay for the last unit; decrementing it at checkout can lock stock forever if
the customer never comes back.

## Decision

`CreateOrder` runs in a transaction, locks the variant rows (`SELECT ... FOR UPDATE`), checks availability, writes the
order with a snapshot of each line (name, label, SKU, unit price) and decrements the stock.

Reserved stock is released by `CancelOrder` when:

- the customer cancels on the payment page,
- Stripe reports the session as expired (`checkout.session.expired`),
- the `orders:release-stale` command, scheduled every ten minutes, finds a pending order older than
  `shop.pending_order_ttl_minutes` (longer than the 30-minute payment session). This covers lost webhooks and failed
  session creation.

PostgreSQL check constraints back the invariant: stock, quantities and amounts can never go negative, and order
totals must add up.

## Consequences

- No overselling, including under concurrent checkouts.
- Stock is unavailable to others while a customer is paying, for at most the TTL.
- Order lines keep displaying what was bought even if the product is later renamed, repriced or deleted.
