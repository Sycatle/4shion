# 2. Database-backed cart, merged on login

- Status: accepted

## Context

Visitors browse and fill a cart before having an account; checkout requires one. A session-only cart is lost when
the session expires or the customer switches device, and it cannot be inspected for support.

## Decision

Carts are rows in `carts` / `cart_items`. A guest cart is identified by a random token kept in the session; a
customer cart by `user_id` (unique). `CartManager` is registered as a scoped service and hides which one applies.

On login, the `MergeGuestCart` listener folds the guest cart into the customer cart, summing quantities and capping
them at the stock and the per-line maximum.

## Consequences

- The cart survives logins, devices and session expiry for customers.
- Abandoned guest carts accumulate; they are cheap rows and can be pruned by age if needed.
- Prices are never stored in the cart: totals are recomputed from the current catalogue by `CartTotals`, so a price
  change is reflected until the order is created.
