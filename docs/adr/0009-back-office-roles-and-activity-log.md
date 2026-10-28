# 9. Back-office roles and activity log without a permission package

- Status: accepted

## Context

The back office is used by three kinds of staff: the owner, warehouse preparers and customer support. Each needs a
different part of the admin, and changes to prices, stock or orders must be traceable to a person. Packages such as
spatie/laravel-permission and spatie/laravel-activitylog cover this, but they bring tables, caches and APIs sized for
fine-grained, user-editable permissions that the shop does not need.

## Decision

- `App\Enums\Role` lists the roles and the back-office areas each one may open (`null` means all of them). A Filament
  resource declares its area through the `RestrictedByRole` concern, and Filament's `canAccess()` checks it.
  Policies still apply on individual records.
- The `LogsActivity` model concern records creations, updates (with the changed attributes, old and new) and
  deletions into `activity_logs`. It records only actions made by an authenticated staff member, so customers and
  queued jobs do not flood the log. Sensitive attributes are dropped.
- Two-factor authentication can be made mandatory for staff (`ADMIN_MFA_REQUIRED`). Separately, the shop
  sends an alert e-mail to any account, customer or staff, that signs in from a new IP address.

## Consequences

- Roles are code and change through a deploy. That fits a team of a few people, and a new area takes one line.
- The log lives in the same database and is browsable in the admin, read-only.
- Moving to a permission package later only means replacing `Role::canAccess()` and the concern.
