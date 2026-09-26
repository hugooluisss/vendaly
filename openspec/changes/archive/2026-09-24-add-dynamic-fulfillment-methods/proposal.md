# Proposal

## Why

Fulfillment methods are currently a fixed set of exactly three (`pickup`, `delivery`, `dine_in`), hardcoded as booleans/fees on the `businesses` table and matched by literal string throughout the order/checkout flow. Payment methods, by contrast, are already a dynamic, owner-named list (`payment_methods` table, its own CRUD endpoints) — a business can call theirs whatever it wants. Business owners need the same flexibility for fulfillment (e.g. "Envío foráneo", "Recolección en sucursal 2"), and today there is no way to add a fourth option. This also removes the current hardcoded assumption that only `delivery` ever needs an address, replacing it with a per-method `requires_address` flag any dynamic method can opt into.

## What Changes

- Replace the three fixed boolean+fee columns on `businesses` with a new `fulfillment_methods` table (id, business_id, name, fee, `requires_address`, position) — mirroring `payment_methods`'s existing shape and CRUD pattern (add/rename/delete, at-least-one-remains guard, no reorder — matching payment-methods' actual scope, not its broken reorder button).
- Every new business is seeded with one default fulfillment method, "Consumo en el local" (`requires_address = false`, no fee), replacing today's implicit default of `pickup_enabled = true`.
- `requires_address` becomes the sole gate for delivery-address/map collection at checkout, replacing the hardcoded `fulfillment_type === 'delivery'` check used today in `OrderService::validateFulfillment()`, `WhatsAppOrderLink`, and the checkout UI.
- Orders snapshot the selected method's name and fee at creation time (`fulfillment_method_id` + `fulfillment_method_snapshot`, mirroring the existing `payment_method_id`/`payment_method_snapshot` pattern) instead of a bare `fulfillment_type` string.
- The public catalog response's `fulfillment_methods` field changes shape from a fixed `{type, fee}` union to a dynamic `{id, name, fee, requires_address}` list, matching how `payment_methods` is already exposed.
- The dashboard's "Métodos de pedido" modal (built earlier this session as a single boolean+fee form) is rewritten into a full add/rename/delete list UI, mirroring the payment-methods modal, plus a fee input and a "requiere dirección" checkbox per method.
- The public checkout page's fulfillment selector shows each method's real name (no more hardcoded label ternary) and shows the address/map fields only when the selected method's `requires_address` flag is true.
- A migration backfills every existing business's current `pickup_enabled`/`delivery_enabled`/`dine_in_enabled` (and fees) into rows in the new table, and every existing order's `fulfillment_type` into `fulfillment_method_id`/`fulfillment_method_snapshot`, before dropping the old columns.

## Capabilities

### Modified Capabilities
- `order-fulfillment`: three requirements whose fundamental mechanism changes (not just wording) are REMOVED and replaced with ADDED equivalents: "Fulfillment method configuration" (enable/disable 3 fixed options → add/rename/delete a dynamic list, seeded with "Consumo en el local"), "Delivery details capture" (literal `delivery` match → any method's `requires_address` flag), "Customer fulfillment selection validated against enabled methods" (fixed type strings → dynamic list ids). A new "Fulfillment method recorded on the order" requirement documents the snapshot-on-order behavior (mirroring the existing payment-method snapshot requirement).
- `public-catalog`: the `fulfillment_methods` field in the public catalog lookup response changes shape from `{type, fee}` to `{id, name, fee, requires_address}`.
- `catalog-management`: the publish precondition wording changes from "enabled" to "configured" (same intent — a business can't publish with zero ways to fulfill an order — but the dynamic model has no separate enable/disable toggle, only add/delete).

### Not modified
- `order-intent`: already describes fulfillment-method selection, fees, and delivery details abstractly (no hardcoded type strings in its spec text) — no delta needed there; only its backing implementation changes.

## Impact

- **Backend**: new `FulfillmentMethod` entity + `CycleFulfillmentMethodRepository` (mirroring `PaymentMethod`/`CyclePaymentMethodRepository`), a new migration (create `fulfillment_methods`, backfill from the 3 fixed columns and `orders.fulfillment_type`, add `fulfillment_method_id`/`fulfillment_method_snapshot` to `orders`, drop the 6 old `businesses` columns and `orders.fulfillment_type`), `BusinessService` (new CRUD methods, updated seed-on-create, updated publish-precondition check), `CatalogService::publicCatalog()`, `OrderService::validateFulfillment()`/`buildOrder()`, `WhatsAppOrderLink`, `PublicEntityMapper::business()`, new routes mirroring payment-methods' routes.
- **Frontend**: `fulfillment-methods-modal` component rewritten to a CRUD list UI, `business-settings.component.ts` updated to seed it from a sorted array instead of a single settings object, `order-checkout` component/template updated to use dynamic names and the `requires_address` flag, `public.models.ts`/`business.models.ts`/`business-api.service.ts` type/method updates.
- **Tests**: significant rewrites to `apps/api/tests/Unit/BusinessAndCatalogManagementTest.php`, `PublicCatalogAndOrdersTest.php` (heaviest impact), `RepositoriesTest.php`, and `apps/api/tests/Functional/CatalogOptionsCest.php`; the fulfillment-methods-modal and order-checkout frontend specs.
- **No reorder support** for fulfillment methods, matching payment-methods' real (working) scope rather than its broken reorder button — this is a deliberate scope decision, not an oversight.
