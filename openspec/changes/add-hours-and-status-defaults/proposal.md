# Proposal

## Why

New businesses today start with no opening hours at all (nothing seeds `business_hours`, and the owner sees a misleading Mon–Sun 09:00–18:00 placeholder that was never actually saved), and with a default order-status pipeline (`Creado` → `Elaborando` → `Entregado`/`Cancelado`) that doesn't reflect how the business actually wants to track an order (no "confirmed" or "delivered vs. done" distinction). This change standardizes both defaults for new businesses and backfills them for existing ones.

## What Changes

- Seed default opening hours (Monday–Friday 09:00–20:00, Saturday/Sunday closed) for every new business in `BusinessService::create()`.
- Backfill the same default hours, via migration, for any existing business that currently has zero `business_hours` rows.
- Update the frontend's pre-save hours placeholder (`business-settings.component.ts`) from "all 7 days, 09:00–18:00" to match the new default (Mon–Fri 09:00–20:00, Sat/Sun closed), since it's shown before the owner has saved anything.
- Replace the default order-status seed list (`OrderStatus::defaults()`) with six statuses, in order: `recibido` (default), `confirmado`, `preparando`, `entregado`, `terminado` (terminal), `cancelado` (terminal). Applies to every new business going forward.
- Backfill existing businesses via migration: rename their current default-named statuses in place (`Creado`→`recibido`, `Elaborando`→`preparando`, `Entregado`→`entregado`, `Cancelado`→`cancelado`), preserving `id`/`is_default`/existing order assignments, then insert `confirmado` and `terminado` for any business missing them (regardless of whether its other names were renamed by the owner). A business's already-customized statuses (names no longer matching the 4 known defaults) are only ever added to, never renamed or removed.
- **Out of scope / already done**: dine-in fulfillment method ("Consumo en el local") and cash payment method ("Efectivo") are already seeded correctly by `BusinessService::create()` — no changes needed there.

## Capabilities

### Modified Capabilities
- `business-management`: "Opening hours configuration" requirement gains a stated default (Mon–Fri 09:00–20:00, Sat/Sun closed) seeded on business creation and backfilled for existing businesses with no hours configured.
- `order-status`: "Order status list management" requirement's default seed list changes from 4 statuses (`Creado`/`Elaborando`/`Entregado`/`Cancelado`) to 6 (`recibido`/`confirmado`/`preparando`/`entregado`/`terminado`/`cancelado`), with `recibido` default and `terminado`/`cancelado` terminal.

## Impact

- Backend only: `apps/api/src/Domain/Service/BusinessService.php` (`create()` seeding), `apps/api/src/Domain/Entity/OrderStatus.php` (`defaults()`), a new migration under `apps/api/migrations/` (hours backfill + status rename/insert backfill).
- Frontend: `apps/web/src/app/dashboard/business-settings/business-settings.component.ts` (pre-save hours placeholder only — no API contract change, `BusinessHour`/`OrderStatus` TS interfaces are unaffected since names/hours are already free-form data, not enums).
- No route, entity-shape, or API-contract changes — this only changes default/seed *values* and adds backfill data for existing rows.
