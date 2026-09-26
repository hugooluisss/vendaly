# Design

## Context

`BusinessService::create()` (`apps/api/src/Domain/Service/BusinessService.php`) already seeds a default `FulfillmentMethod` ("Consumo en el local") and a default `PaymentMethod` ("Efectivo") when a business is created, plus a loop seeding `OrderStatus::defaults()`. It does not seed any `BusinessHours` rows at all. `OrderStatus::defaults()` (`apps/api/src/Domain/Entity/OrderStatus.php`) currently returns 4 entries (`Creado`/`Elaborando`/`Entregado`/`Cancelado`). Both tables (`business_hours`, `order_statuses`) are per-business, owner-editable, free-text rows — not enums — so "changing the default" only affects future seeding; existing rows need an explicit backfill migration, mirroring the shape of the existing `20260922.000001_13_order_status_seed_repair.php` repair migration.

## Goals / Non-Goals

- Goal: new businesses get sensible hours and a richer status pipeline out of the box.
- Goal: existing businesses converge onto the same defaults without silently discarding an owner's customizations or breaking `orders.status_id` references.
- Non-goal: touching fulfillment/payment method seeding (already correct).
- Non-goal: any new API surface, entity column, or route — this is seed-data and migration only.

## Decisions

### Business hours: seeding + backfill

- **Seeding** (`BusinessService::create()`): after creating the `Business`, insert 7 `BusinessHours` rows using the existing `day_of_week` convention (0=Sunday..6=Saturday, per `20260916.000000_01_initial.php`'s `CHECK (day_of_week BETWEEN 0 AND 6)`): days 1–5 (Mon–Fri) `opens_at='09:00:00'`, `closes_at='20:00:00'`, `is_closed=false`; days 0 and 6 (Sun/Sat) `is_closed=true`, `opens_at`/`closes_at` null. Mirrors the existing fulfillment/payment-method seeding pattern already in `create()` (same method, same transaction).
- **Backfill migration**: `INSERT INTO business_hours (business_id, day_of_week, opens_at, closes_at, is_closed) SELECT b.id, d.day, ... FROM businesses b CROSS JOIN (VALUES (0),(1),...,(6)) AS d(day) WHERE NOT EXISTS (SELECT 1 FROM business_hours WHERE business_id = b.id)` — a business is only touched if it has zero existing hour rows (i.e., the owner never configured or saved hours), so a business with even one row (including one explicitly marked closed) is left alone entirely. No `UPDATE`/rename involved since there's no existing data to preserve — either a business has rows or it doesn't.

### Order statuses: seeding + backfill

- **Seeding** (`OrderStatus::defaults()`): replace the 4-entry array with 6, in position order:
  ```
  ['name' => 'recibido',   'color' => '#EA580C', 'is_terminal' => false, 'is_default' => true,  'position' => 0],
  ['name' => 'confirmado', 'color' => '#2563EB', 'is_terminal' => false, 'is_default' => false, 'position' => 1],
  ['name' => 'preparando', 'color' => '#7C3AED', 'is_terminal' => false, 'is_default' => false, 'position' => 2],
  ['name' => 'entregado',  'color' => '#0891B2', 'is_terminal' => false, 'is_default' => false, 'position' => 3],
  ['name' => 'terminado',  'color' => '#16A34A', 'is_terminal' => true,  'is_default' => false, 'position' => 4],
  ['name' => 'cancelado',  'color' => '#DC2626', 'is_terminal' => true,  'is_default' => false, 'position' => 5],
  ```
  Colors are new picks for `confirmado`/`preparando`/`entregado` (the old 4-status palette only had 4 distinct colors) chosen to stay visually distinct; not a spec-level concern, so left to implementation to adjust if design review wants different hex values.
- **Backfill migration**, per business, in this order (mirroring `20260922.000001_13_order_status_seed_repair.php`'s idempotent `NOT EXISTS` guard style):
  1. `UPDATE order_statuses SET name = 'recibido' WHERE business_id = ? AND name = 'Creado'` (and similarly `Elaborando`→`preparando`, `Entregado`→`entregado`, `Cancelado`→`cancelado`) — a plain rename-in-place, so `id`, `is_default`, `is_terminal`, `position`, and every `orders.status_id` pointing at that row are untouched. Only fires when the name still matches exactly one of the 4 known defaults; a business that already renamed/deleted a status is simply not matched by this `WHERE`, so it's implicitly skipped — no separate "is this business customized" check needed.
  2. `INSERT INTO order_statuses (business_id, name, color, is_terminal, is_default, position) SELECT b.id, 'confirmado', '#2563EB', false, false, <position> FROM businesses b WHERE NOT EXISTS (SELECT 1 FROM order_statuses WHERE business_id = b.id AND name = 'confirmado')`, and the equivalent for `terminado` — runs for every business regardless of whether step 1 renamed anything, so a business with fully custom status names still gets `confirmado`/`terminado` added (per the confirmed scope: "always insert confirmado/terminado if missing regardless"). Position: insert `confirmado` at `position = (position of recibido/Creado, if found, else 0) + 1`-ish is over-engineering — simplest correct approach is to insert both new statuses at the end of that business's current max `position + 1`/`+2`; exact position is a display-order nicety the owner can always reorder, not worth complex position-shifting logic per business.

## Risks / Trade-offs

- **Position ordering after backfill for renamed businesses**: renamed statuses keep their original `position`, so `confirmado`/`terminado` land at the end of the list rather than interleaved in the ideal recibido→confirmado→preparando→entregado→terminado→cancelado order. Accepted trade-off — owners can manually reorder (existing "Reorder statuses" capability), and shifting every other status's position to interleave correctly risks off-by-one bugs for comparatively little value on a data migration that runs once.
- **Businesses with only some of the 4 default names customized** (e.g. owner renamed "Cancelado" to "Rechazado" but left the other 3): those 3 still get renamed (`Creado`→`recibido` etc.), and "Rechazado" is left alone — this matches "rename only exact matches" but means the resulting list is a mix of new and owner-chosen names, which is expected/intended per the confirmed scope.
- **No test-data risk**: this project's dev Postgres/MySQL DB has real leftover rows from prior test runs (per CLAUDE.md) — the backfill migration must be safe to run against that data (idempotent `NOT EXISTS`/exact-name-match guards handle this; no assumption that all businesses look alike).
