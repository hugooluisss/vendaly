# Design

## Context

`Business` (`apps/api/src/Domain/Entity/Business.php`, table `businesses`) already stores `latitude`/`longitude` as plain nullable decimal columns and `isPublished` as a boolean — the established pattern for small, fixed, non-extensible business attributes is columns directly on `businesses`, not a child table (child tables in this codebase are reserved for open-ended, business-owner-defined lists like `product_ingredients` and the newer `product_options`/`product_option_values`). The three fulfillment methods are a small, fixed, non-extensible set (not something a business owner names/creates), so they follow the column pattern, not the child-table pattern. `Order` (table `orders`) already snapshots order-level state (`total`, `customer_note`) directly on the row. See `proposal.md` for motivation and `specs/order-fulfillment/spec.md`, `specs/catalog-management/spec.md`, `specs/public-catalog/spec.md`, `specs/order-intent/spec.md` for requirements.

## Goals / Non-Goals

**Goals:**
- Model the three fulfillment methods as booleans (plus an optional fee each) on `businesses`, since the set is fixed (not business-owner-extensible) and always exactly `pickup`/`delivery`/`dine_in`.
- Model payment methods as an open, business-owned named list, following the exact same pattern as `ProductIngredient` — the owner names their own entries, not a fixed set.
- Guarantee no business (new or pre-existing) ever ends up with zero enabled fulfillment methods or zero payment methods, so catalog publish is never silently blocked by this change for a business that predates it.
- Split the customer flow into cart-review (no submit) and checkout (method/payment selection, fee/total breakdown, submit) as two distinct routes/steps.
- Reuse the existing `shared/map` Leaflet/OpenStreetMap component for the delivery pin, the same one used for business location today — no new mapping dependency.

**Non-Goals:**
- Delivery radius/zone restrictions or ETA estimates.
- Per-payment-method fees (only fulfillment methods carry a fee in this version).
- Table/seat selection for dine-in (the user only asked for the method itself, not table management).
- A business-configurable set of fulfillment methods beyond these three fixed ones (payment methods, by contrast, are open/named — see below).
- Server-side storage of a customer "default" delivery location — explicitly browser-local only per the user's decision.

## Decisions

**Three boolean columns on `businesses`** (`pickup_enabled`, `delivery_enabled`, `dine_in_enabled`), plus a matching nullable decimal fee column per method (`pickup_fee`, `delivery_fee`, `dine_in_fee`, same `decimal` shape as `products.price`), not a child table. Alternative considered: a `business_fulfillment_methods` join table (one row per enabled method, with a `fee` column) — rejected as overkill for a fixed 3-value set; six columns are simpler to query, index-free, and match the `isPublished`-style pattern already on this entity. A `null`/unset fee means no fee (not zero-as-a-value ambiguity).

**`PaymentMethod` as a child table** (`payment_methods`: id, business_id, name, position), an exact mirror of `ProductIngredient`/`product_ingredients` (`apps/api/src/Domain/Entity/ProductIngredient.php`) — same reasoning: it's an open, business-owner-named list, not a fixed set, so it follows the child-table pattern, not the boolean-column pattern used for fulfillment methods. `BusinessService::create` seeds one row named "Efectivo" for every newly created business, in the same transaction as the business row itself.

**Migration default: `pickup_enabled` defaults to `true`, `delivery_enabled` and `dine_in_enabled` default to `false`.** This satisfies "at least one enabled" for every pre-existing business the moment the migration runs (no business had any fulfillment concept before, so none had a way to end up in an invalid state), and picking `pickup` as the default is the one method with no dependency on address/geocoding data a business may not have filled in — it always "just works" post-migration.

**"At least one enabled" enforced in `BusinessService`** (or a small helper it uses) at the point of an update request: before persisting a change that would disable a method, check the resulting state has ≥1 of the three still `true`; reject with a `DomainException` (422, consistent with existing validation errors) if not. This mirrors how `CategoryService::delete` already blocks a delete that would violate an invariant (a category with existing products).

**Catalog publish precondition lives in `BusinessService`'s publish method** (or `CatalogService`, wherever publish currently lives) as an added check: if none of the three fulfillment booleans is `true`, or the business has zero payment methods, reject the publish request the same way an invalid WhatsApp number or missing required field is rejected elsewhere. In practice the payment-method half of this check should never fire post-migration (every business has ≥1 seeded/guaranteed), but it stays as a real guard, not a comment, mirroring the fulfillment check's defensiveness.

**Order fulfillment/payment fields directly on `orders`**: `fulfillment_type` (`varchar`, one of `pickup`/`delivery`/`dine_in`), `fulfillment_fee_snapshot` (nullable `decimal`, the method's fee *at order time*), `delivery_address` (nullable `text`), `delivery_latitude`/`delivery_longitude` (nullable `decimal`, same shape as `Business.latitude`/`longitude`), `payment_method_id` (nullable `bigint`, `ON DELETE SET NULL` FK to `payment_methods`, for traceability), `payment_method_snapshot` (`varchar`, the name at order time). Snapshot-style throughout, matching how `OrderItem` already snapshots product name/price at creation time — an order's fulfillment/payment details never change after creation, independent of any later change to the business's methods.

**`orders.total` becomes subtotal-plus-fee**: the existing per-item total calculation is unchanged; after summing items, add `fulfillment_fee_snapshot` (0 if null) to get the persisted `total`. The subtotal itself isn't a stored column — it's derivable as `total - fulfillment_fee_snapshot` wherever needed (WhatsApp message, order history), avoiding a redundant stored value that could drift from its inputs.

**Fulfillment/payment/delivery validation lives in `OrderService`** at order-creation time, alongside the existing item/option validation added in the previous `add-product-options` change: reject if `fulfillment_type` is missing, not one of the three, or not currently enabled for the business; for `delivery`, reject if neither `delivery_address` nor both coordinates are present; reject if `payment_method_id` is missing or doesn't currently belong to the business. All-or-nothing rejection (no partial order), consistent with existing behavior.

**WhatsApp message**: append a fulfillment-method line (e.g. "Entrega a domicilio", "Recolección en tienda", "Consumo en el local"), a fee line only when non-zero, delivery address/map-link lines when applicable, a payment-method line, and a subtotal/fee/total breakdown (fee line and separate "Total" line only appear when there's a fee to show, to avoid cluttering the common no-fee case) — plain text, no new dependency, consistent with the existing `wa.me` deep-link approach.

**Two-step checkout as two Angular routes**: the existing `/public/catalog/:slug/order` route (currently `OrderSummaryComponent`) keeps cart-review responsibilities only — items, quantities, notes, a "Continuar" button — and loses its submit/WhatsApp logic. A new route (e.g. `/public/catalog/:slug/order/checkout`) hosts a new component with the fulfillment/payment selectors, fee/total breakdown, delivery address/pin fields, and the "Enviar por WhatsApp" action — it reads the same shared `CartService` (already `providedIn: 'root'`, no new state plumbing needed) rather than receiving cart data via route params.

**Client-remembered delivery location**: a small `localStorage`-backed read/write in the delivery form component (or a tiny dedicated service, mirroring `CartService`'s existing `load`/`persist` try/catch pattern), keyed separately from the cart (e.g. `vendaly.delivery-location`). Purely a browser convenience; every order submission still sends whatever is currently in the form, whether prefilled or freshly entered.

**Map pin reuse**: the delivery picker reuses `apps/web/src/app/shared/map/map.component.ts` in "pick a point" mode, the same component `dashboard/business-settings` already uses for business coordinates — no new map integration.

## Risks / Trade-offs

- [A business owner disables `delivery` while a customer has that catalog page open with `delivery` already selected] → Mitigated by re-validating the selected method against the business's *currently* enabled methods at order-creation time on the server; the client-side selection is advisory, same trust boundary as the existing product-option validation.
- [Adding a delivery form (address + map) to the order-summary screen increases its complexity] → Scoped additively: it only appears when `delivery` is the selected method; `pickup`/`dine_in` orders see no new fields.
- [`localStorage` delivery prefill could show a stale/wrong address if the customer moved or is ordering from a different business] → Acceptable, same class of limitation as any browser-remembered form value; it's a convenience, always editable before submit, never silently submitted without being shown to the customer first.
- [A pre-existing business's cart-review route is bookmarked/linked from before this change and a customer lands there expecting a submit button] → Low risk (this app has no persistent customer-facing links to that specific step beyond normal in-app navigation); the cart-review step's new "Continuar" button makes the two-step flow discoverable immediately.

## Migration Plan

Additive: fulfillment boolean+fee columns on `businesses` (six total), a new `payment_methods` table, and fulfillment/payment columns on `orders`. Pre-existing businesses get `pickup_enabled DEFAULT true` (no fee) via column default, and need a one-time data backfill inserting an "Efectivo" `payment_methods` row for each existing business (the seed-on-create logic only fires for new businesses going forward) — a small migration-time `INSERT ... SELECT` over existing `businesses` rows, not a script. Rollback is dropping the new columns/table; no existing behavior depends on them being present.
