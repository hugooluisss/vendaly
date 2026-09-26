# Tasks

## 1. Backend: default seeding on business creation

- [x] 1.1 In `apps/api/src/Domain/Service/BusinessService.php`'s `create()`, add seeding of 7 `BusinessHours` rows for the new business, mirroring the existing fulfillment/payment-method seeding style in the same method: days 1–5 (Mon–Fri, using the `day_of_week` convention 0=Sunday..6=Saturday from `apps/api/migrations/20260916.000000_01_initial.php`) with `opensAt='09:00:00'`, `closesAt='20:00:00'`, `isClosed=false`; days 0 and 6 (Sun/Sat) with `isClosed=true`, `opensAt`/`closesAt` null
- [x] 1.2 In `apps/api/src/Domain/Entity/OrderStatus.php`'s `defaults()`, replace the 4-entry array with the 6 entries from `design.md` (`recibido`/`confirmado`/`preparando`/`entregado`/`terminado`/`cancelado`, positions 0-5, `recibido` `is_default=true`, `terminado`/`cancelado` `is_terminal=true`)

## 2. Backend: backfill migration

- [x] 2.1 Create `apps/api/migrations/20260924.000002_18_hours_and_status_defaults.php` (MySQL syntax, matching the project's other migrations) that:
  - Backfills default business hours for any business with zero `business_hours` rows: for each of the 7 `day_of_week` values, `INSERT INTO business_hours (business_id, day_of_week, opens_at, closes_at, is_closed) SELECT b.id, <day>, <opens>, <closes>, <is_closed> FROM businesses b WHERE NOT EXISTS (SELECT 1 FROM business_hours WHERE business_id = b.id)`, using `09:00:00`/`20:00:00`/`false` for days 1–5 and `NULL`/`NULL`/`true` for days 0 and 6
  - Renames each business's default-named order statuses in place (preserving `id`, `is_default`, `is_terminal` unless changing per the new scheme, `position`, and all `orders.status_id` references): `UPDATE order_statuses SET name = 'recibido' WHERE name = 'Creado'`, and similarly `Elaborando`→`preparando`, `Entregado`→`entregado`, `Cancelado`→`cancelado` (exact-name-match only; a business with already-customized names is simply not matched, so it's left alone by these statements)
  - Inserts `confirmado` and `terminado` for every business that doesn't already have a status with that name: `INSERT INTO order_statuses (business_id, name, color, is_terminal, is_default, position) SELECT b.id, 'confirmado', '#2563EB', FALSE, FALSE, (SELECT COALESCE(MAX(position), -1) + 1 FROM order_statuses os2 WHERE os2.business_id = b.id) FROM businesses b WHERE NOT EXISTS (SELECT 1 FROM order_statuses WHERE business_id = b.id AND name = 'confirmado')`, and the equivalent for `terminado` (`is_terminal = TRUE`) — run these after the renames so the `MAX(position)` subquery reflects the post-rename state
- [x] 2.2 Run `docker exec vendaly-api-1 php bin/migrate.php` against `dev_vendaly` and verify it completes with no errors
- [x] 2.3 Manually verify against the dev database: pick a business that had unmodified default statuses before the migration — confirm its `order_statuses` rows are renamed in place with unchanged `id`s and that any of its orders still resolve their assigned status correctly; pick a business (if any exists) with customized/renamed statuses — confirm its existing statuses are untouched and it now has `confirmado`/`terminado` appended; confirm a business with zero `business_hours` rows before the migration now has all 7, and a business that already had at least one row is unaffected

## 3. Frontend: pre-save hours placeholder

- [x] 3.1 In `apps/web/src/app/dashboard/business-settings/business-settings.component.ts`, update the `hours` field initializer (currently `{ day_of_week: day, opens_at: '09:00', closes_at: '18:00', is_closed: false }` for all 7 days) to match the new backend default: Mon–Fri (`day_of_week` 1–5) `opens_at: '09:00'`, `closes_at: '20:00'`, `is_closed: false`; Sun/Sat (`day_of_week` 0, 6) `is_closed: true`
- [x] 3.2 Update `business-settings.component.spec.ts` if it asserts on the old placeholder values (verified: no spec in the repo asserts the old `18:00` placeholder value; none needed updating)

## 4. Verification

- [x] 4.1 Run `docker exec vendaly-api-1 vendor/bin/codecept run Unit` and confirm the full suite passes, including any new/updated assertions on `BusinessService::create()`'s seeded hours and the new 6-status default list (95 tests, 287 assertions — also independently reran `Functional` suite after fixing a stale `OrderStatusCest` assertion the first pass missed: 8 tests, 42 assertions, all green)
- [x] 4.2 Run `npx ng build` and the frontend test suite for the touched spec file, confirm passing (build passes with pre-existing CommonJS warnings only; no dedicated `business-settings.component.spec.ts` exists in this repo, and Karma/Chrome isn't available in this environment, so this could not be run — not verified)
- [ ] 4.3 Manually create a new business in the running app and confirm: its hours show Mon–Fri 09:00–20:00 with Sat/Sun marked closed before ever saving, and its order-status list (in the order-statuses management modal) shows the 6 new statuses in the expected order with "recibido" as default (not verified: browser/dashboard session unavailable in this environment)
