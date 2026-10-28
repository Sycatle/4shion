# 7. Stock changes go through a ledger

- Status: accepted

## Context

Stock moves for many reasons: sales, cancelled or expired orders, refunds, restocks, manual corrections from the
back office and pre-orders served when goods arrive. With `stock` updated in place from each of those places, a wrong
count cannot be explained after the fact, and pre-orders would need their own allocation logic at every restock site.

Deriving the stock from the sum of movements was considered. It makes every catalogue read an aggregate, and the
listing filters and sorts on availability.

## Decision

`App\Inventory\StockLedger` is the only writer of `product_variants.stock`. `add()` and `remove()` lock the variant
row and update the counter. They also append a `stock_movements` row with the signed quantity, the stock after the
move, the reason (`App\Enums\StockMovementReason`) and the order or staff member responsible.

Incoming stock serves outstanding pre-orders first, oldest order first, and each allocation is recorded as its own
movement. When a variant goes from zero to positive stock, the ledger fires `VariantRestocked`, which sends the
back-in-stock alerts.

## Consequences

- The counter stays cheap to read, and the ledger explains every unit: the back office shows the movements per variant.
- The `stock >= 0` check constraint still guards the counter if a write ever bypasses the ledger.
- Cancellation, expiry and refund share `RestoreOrderResources`. It clears the order's pre-orders before putting stock
  back, so that the returned units are not allocated to the same order again.
