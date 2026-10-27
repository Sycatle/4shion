# 1. Conventional Laravel layout with single-purpose actions

- Status: accepted

## Context

The shop has a handful of business operations (create an order, mark it paid, ship, refund, cancel) that are
triggered from several entry points: Livewire components, the Filament admin, Stripe webhooks and a scheduled
command. Putting the logic in any one of those would duplicate it in the others.

A layered or hexagonal architecture (repositories, domain entities separate from Eloquent) was considered. For a
catalogue-and-orders domain of this size it would mostly add mapping code without isolating anything that changes
independently.

## Decision

Keep Laravel's default layout and Eloquent models, and move each business operation into a final, invokable class
under `app/Actions/{Area}`. Entry points stay thin: they validate input, call an action and render the result.

Supporting code sits next to its concern: `app/Cart` (cart persistence and totals), `app/Payments` (provider
abstraction), `app/Data` (value objects), `app/Enums` (the order lifecycle).

## Consequences

- Any Laravel developer finds their way without a guide; the actions are the only extra convention.
- Actions are resolved from the container, so their dependencies (gateway, cart) are swappable in tests.
- Architecture tests (`tests/Unit/ArchTest.php`) keep the boundaries: actions are final and invokable, domain code
  does not depend on Livewire or Filament, and Stripe is only reached through `app/Payments`.
