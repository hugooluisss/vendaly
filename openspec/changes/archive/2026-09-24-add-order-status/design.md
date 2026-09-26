# Design

## Context

`CycleOrderRepository::createWithItems` (`apps/api/src/Infrastructure/Cycle/Repository/CycleOrderRepository.php`) already wraps order + items (+ item-options) creation in a single DB transaction. `PaymentMethod`/`payment_methods` (added in `add-order-fulfillment`) is the established pattern for a business-owned, named, ordered child list with seed-on-create and delete guards — `order_statuses` follows the same shape with extra columns. See `proposal.md` for motivation and `specs/order-status/spec.md`, `specs/order-intent/spec.md`, `specs/order-history/spec.md` for requirements.

## Goals / Non-Goals

**Goals:**
- Generate a per-business sequential order number with no risk of two concurrent orders on the same business getting the same number.
- Reuse the exact `payment_methods` pattern (table shape, seed-on-create, CRUD guards) for `order_statuses`, adding only the columns that differ (color, is_terminal, is_default).
- Leave existing orders/businesses in a valid, displayable state after migration (numbered, statused) rather than nullable/blank.

**Non-Goals:**
- Status-transition rules (e.g. blocking a terminal status from being changed again) — the user asked for a terminal *flag*, not enforcement; any status can move to any other status in this version.
- Notifying the customer of status changes (no customer accounts/push channel exists).
- Multi-currency or configurable order-number formats (prefixes, zero-padding) — a plain per-business integer.

## Decisions

**Order number via an atomic counter column on `businesses`**, not a `MAX(order_number) + 1` query. Add `next_order_number INTEGER NOT NULL DEFAULT 1` to `businesses`. Inside the same transaction `CycleOrderRepository::createWithItems` already opens, run `UPDATE businesses SET next_order_number = next_order_number + 1 WHERE id = :id RETURNING next_order_number - 1` to atomically claim the current order's number and advance the counter in one statement — this avoids the race two concurrent `MAX()`-based inserts would hit (both reading the same max before either commits). Alternative considered: a `SELECT ... FOR UPDATE` row lock on the business plus a separate `MAX()` query — rejected as two round-trips doing what one atomic `UPDATE ... RETURNING` does.

**`order_statuses` mirrors `payment_methods`** (`id`, `business_id` FK cascade, `name`, `position`) plus `color VARCHAR(7)` (hex), `is_terminal BOOLEAN NOT NULL DEFAULT FALSE`, `is_default BOOLEAN NOT NULL DEFAULT FALSE`. A partial unique index (`WHERE is_default`) isn't used — "exactly one default" is enforced in the service layer (unsetting the old default and setting the new one in the same update), consistent with how "at least one fulfillment method enabled" is enforced in `BusinessService` rather than a DB constraint.

**`Order` gains `orderNumber` (int) and `statusId`** (nullable `bigint`, FK to `order_statuses`, **`ON DELETE RESTRICT`** — not `SET NULL` like `payment_method_id`). This is deliberate: the service-layer guard already blocks deleting a status that's in use, so the FK constraint is a second line of defense that should never actually fire; `RESTRICT` makes that defense real (a `SET NULL` FK would silently let a status disappear from under an order if the service guard were ever bypassed or buggy) rather than decorative.

**Status-list CRUD and guards live in `BusinessService`** (mirroring the existing `payment_methods` methods added in `add-order-fulfillment`): add/rename/recolor/reorder/delete, delete rejected when it's the last one, delete rejected when it's the current default, delete rejected when `OrderRepository`/`OrderStatusRepository` reports the status is referenced by ≥1 order (a small existence check, not a full order load). Setting a status as default unsets the previous default in the same service call (read current default, if different from the target, flip both in one update).

**Default-status assignment lives in `OrderService::create`**, alongside the existing fulfillment/payment-method validation: after validating the order, look up the business's current default status (a single indexed query, `WHERE business_id = ? AND is_default = true`) and assign it to the new `Order`.

**Order-history status update is a new `BusinessService`/`OrderService` method** (whichever currently owns `listForBusiness` — read `OrderController.php` to place it consistently) taking `(userId, businessId, orderId, statusId)`, guarded the same way as `listForBusiness` (ownership check), validating the target status belongs to that business.

**WhatsApp message**: prepend or insert an "Pedido #<n>" line near the top of the message (before the item list), consistent with how the fulfillment method/payment lines were appended in `add-order-fulfillment` — a plain text line, no formatting dependency.

## Risks / Trade-offs

- [Two orders racing on the same business could still collide if `createWithItems`'s transaction isn't actually atomic end-to-end for the counter update] → Mitigated by doing the counter `UPDATE ... RETURNING` inside the exact same transaction/connection the order insert uses, not a separate connection or pre-transaction step.
- [A status color with no format validation could break the dashboard badge rendering] → Validate `color` is a `#RRGGBB` hex string server-side (reject otherwise), consistent with how the existing coordinate/fee validations reject malformed input rather than trusting the client.
- [Businesses that predate this change need a valid `next_order_number`, retroactive order numbers, and a status on every existing order] → See Migration Plan.

## Migration Plan

Additive, with two backfills (like `add-order-fulfillment`'s payment-methods backfill):
1. Add `next_order_number` to `businesses` (default 1) and `order_number`/`status_id` to `orders` (nullable initially, so the migration itself doesn't need to compute values inline).
2. Add `order_statuses` table, then backfill: `INSERT INTO order_statuses (business_id, name, color, is_terminal, is_default, position) SELECT id, 'Creado', '#EA580C', false, true, 0 FROM businesses` (and three more inserts for the other seed statuses), same shape as the `payment_methods` backfill.
3. Backfill every pre-existing order's `order_number` via a per-business row-number ordered by `created_at, id` ascending (a single `UPDATE ... FROM (SELECT id, ROW_NUMBER() OVER (PARTITION BY business_id ORDER BY created_at, id) AS rn FROM orders) t WHERE orders.id = t.id SET order_number = t.rn` style statement), and set each business's `next_order_number` to `MAX(order_number) + 1` per business afterward (a one-time `MAX()` computation is fine here since it's a single migration-time pass, not a concurrent runtime path).
4. Backfill every pre-existing order's `status_id` to that business's seeded "Creado" status (the default), so no historical order displays a blank status.
5. Only after all four backfills, add `NOT NULL` constraints to `orders.order_number` and `orders.status_id`.

Rollback is dropping the new columns/table; no existing behavior depends on them being present.
