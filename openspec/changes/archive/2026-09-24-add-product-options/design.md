# Design

## Context

`ProductIngredient` (`apps/api/src/Domain/Entity/ProductIngredient.php`, table `product_ingredients`) is the closest existing pattern: a simple ordered child table (`product_id`, `name`, `position`) managed via `replaceIngredients`-style full-replace in `ProductService`. `OrderItem` (table `order_items`) snapshots `product_name_snapshot` and `unit_price_snapshot` at order time rather than joining back to live product data — orders must remain accurate even if the product changes later. See `proposal.md` for motivation and `specs/product-options/spec.md`, `specs/catalog-management/spec.md`, `specs/public-catalog/spec.md`, `specs/order-intent/spec.md` for requirements.

## Goals / Non-Goals

**Goals:**
- Model option groups/values as first-class, business-owner-editable entities following the existing ingredient-list pattern (child tables, full-replace-on-edit from the dashboard).
- Snapshot selected option values on `OrderItem` at order time, same rationale as the existing name/price snapshot, so a later edit to a group/value never changes a past order's WhatsApp message or record.
- Server-side validation of selections against `required`/`single`/`multiple` constraints on order creation, since the client is untrusted.

**Non-Goals:**
- Per-value inventory/stock tracking.
- Option groups shared/reused across multiple products (each group belongs to exactly one product, mirroring `ProductIngredient`'s per-product scoping — no template/library of reusable groups in this version).
- Nested or dependent option groups (e.g. a value that reveals another group).

## Decisions

**Two tables, not one JSON column.** `product_options` (group) and `product_option_values` (choice within a group), both FK'd with `ON DELETE CASCADE`, mirroring `product_ingredients`. Alternative considered: a single JSON column on `products` for option config — rejected because it can't be validated at the DB layer, complicates server-side selection validation (needs structured querying by id), and breaks from the repo's existing relational-child-table convention.

**Selections snapshotted on a new `order_item_options` table, not FK'd to live `product_option_values` for display.** Each row stores `order_item_id`, `product_option_value_id` (FK, for traceability/analytics), plus snapshotted `option_name`, `value_name`, `price_delta_snapshot` (same snapshot rationale as `OrderItem.productNameSnapshot`/`unitPriceSnapshot`). The WhatsApp message and any future order-history display read the snapshot, not the live value, so a business owner editing "Splenda" to "Stevia" tomorrow doesn't rewrite yesterday's order.

**`selection_type` as a string enum column (`'single'|'multiple'`), validated in the service layer**, consistent with how the rest of the codebase (e.g. business category) uses plain string columns rather than DB enum types, for Postgres migration simplicity.

**`price_delta` stored as `decimal`, nullable-free, defaulting to `'0'`** — matches `products.price`'s decimal type (string in PHP, per Cycle's decimal handling already used for `Order.total`/`OrderItem.unitPriceSnapshot`).

**Priced-option-on-priceless-product guard lives in `ProductService`**, not the DB, since it's a cross-field business rule (needs to read the product's price when validating an option value's delta), matching how similar cross-entity validation already happens in the service layer rather than via DB constraints.

**Server-side selection validation lives in `OrderService`** at order-creation time: for each submitted `OrderItem`, load the product's current `ProductOption`/`ProductOptionValue`s, check (a) every submitted value id belongs to a group on that product, (b) every `required`+`single` group has exactly one submitted value from its own values, (c) `single` groups never receive more than one submitted value. Reject the whole order (no partial creation) on any violation, consistent with the existing "empty selection" / "slug not published" all-or-nothing rejection behavior.

## Risks / Trade-offs

- [Business owner deletes a group/value that's referenced by a past order] → Mitigated by snapshotting name/price on `order_item_options`; the FK to `product_option_values` uses `ON DELETE SET NULL` (not cascade) so historical orders keep their snapshot even if the source value is later deleted.
- [Frontend cart price calculation duplicating backend validation logic, risking drift] → Client-side calculation is advisory only (lets the customer see a running total before submitting); the server is the source of truth and independently validates/recomputes on `POST /public/orders`, so a client bug can't produce an incorrect persisted total.
- [Adding option selection UI to the existing catalog-page/order-summary flow increases its complexity] → Scoped to additive UI (a selector per option group shown only when a product has groups); products without options render unchanged.

## Migration Plan

Additive only: three new tables (`product_options`, `product_option_values`, `order_item_options`), no changes to existing table schemas. Existing products/orders are unaffected (empty option lists, no `order_item_options` rows). Standard forward-only Cycle migration via `bin/migrate.php`; rollback is dropping the three new tables, no data backfill needed since nothing existing depends on them.
