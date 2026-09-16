## Context

`Product` already follows the `Category`/`ProductImage` pattern of one-table-per-concern with Cycle ORM repositories, and already has soft-delete (`deleted_at`) implemented for `Category`/`Product` (see the now-archived `vendaly-mvp-foundation` change). Controllers → services → repositories layering is established; `CatalogService::publicCatalog()` already hand-builds the public response array.

## Goals / Non-Goals

**Goals:**
- Let a business owner attach a simple, ordered, named ingredient list to a product.
- Surface that list on the public catalog per product.
- Keep ingredients fully separate from `ProductOption`/`ProductOptionValue` (still unimplemented, reserved for priced variants) so this change doesn't block that future work.

**Non-Goals:**
- No pricing, no per-order selection, no effect on `OrderService`/`WhatsAppOrderLink` — ingredients never appear in an order or its WhatsApp message.
- No ingredient reuse/autocomplete across products in this version (e.g. a shared ingredient catalog) — each ingredient row belongs to exactly one product.

## Decisions

- **New table `product_ingredients`** (`id`, `product_id` FK, `name`, `position`), mirroring the existing `ProductImage`/`CycleProductRepository` pattern, rather than a single delimited text column on `Product`. Rationale: individual add/remove/reorder in the dashboard UI is much cleaner against discrete rows than parsing/serializing a blob string, and it matches the file/entity-per-concern convention already used in this codebase (alternative considered: a `TEXT` column with comma-separated values — rejected as harder to edit safely and inconsistent with the existing pattern).
- **Replace-on-save semantics**: editing a product's ingredients replaces the full list (delete existing rows for that product, insert the submitted set) rather than diffing add/remove — simplest correct behavior for a list with no independent identity outside its product, and matches the "no ingredient reuse" non-goal.
- **No soft-delete on `product_ingredients` itself**: ingredients have no lifecycle independent of their product (they aren't referenced from orders or anywhere else), so replace-on-save can hard-delete the old rows for that product. Only `Product` and `Category` need soft-delete, because those are referenced elsewhere (e.g. `OrderItem` snapshots the product at order time, independent of live product state).
- **Public catalog**: `CatalogService::publicCatalog()` gains one additional query (ingredients for the active products already being fetched) and adds an `ingredients: string[]` array to each product's array; empty array when none exist (never omit the field, per the spec scenario), so frontend code doesn't need to special-case its absence.
- **Product multipart payload**: send ingredients as one multipart field named `ingredients` containing a JSON array string, for example `{"ingredients":"[\"Tomato\",\"Cheese\"]"}`. JSON requests may send the array directly. Repeated `ingredients[]` fields are not used.

## Risks / Trade-offs

- **N+1 risk**: fetching ingredients per product individually in a loop would repeat the same query-batching mistake already fixed once in `OrderService`. Mitigation: fetch all ingredients for the business's active product ID set in one query (`WHERE product_id IN (...)`), then group in memory — same batching approach already used for `findActiveByIdsForBusiness`.
- **Replace-on-save discards ingredient IDs**: fine here since nothing references an individual ingredient row by ID (unlike `Product`/`Category`, which orders and other data point to) — no migration risk from choosing this simpler approach.
