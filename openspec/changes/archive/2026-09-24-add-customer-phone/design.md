# Design

## Context

`Business.whatsappNumber` is validated in `BusinessService` with `preg_match('/^\+?[1-9][0-9 ()-]{6,20}$/', $number)`. `OrderService::create` already resolves/validates several things before persisting an order (fulfillment method, payment method, options) in the same all-or-nothing style, inside `CycleOrderRepository::createWithItems`'s single transaction. `CartService`/`DeliveryLocationService` (`apps/web/src/app/public/`) already establish the `localStorage` try/catch remember-for-prefill pattern used across this app's public checkout flow. See `proposal.md` for motivation and `specs/customer-records/spec.md`, `specs/order-intent/spec.md`, `specs/order-history/spec.md` for requirements.

## Goals / Non-Goals

**Goals:**
- A `Customer` is strictly scoped to one business — no cross-business identity, no shared table row for "the same person" ordering from two businesses.
- Phone validation reuses the exact regex already trusted for business WhatsApp numbers, rather than inventing a second phone-format rule.
- Customer resolution (find-or-create) happens inside the same transaction as order creation, so a failed order never leaves an orphaned `Customer` with no order, and a customer lookup race (two near-simultaneous first orders from the same new phone) can't create two rows for what should be one customer.

**Non-Goals:**
- Customer accounts, login, or order-history visibility for the customer themselves — this is business-owner-facing identification only.
- A customer profile beyond phone number (no name, address book, order-count aggregate, etc. in this version) — `order-history` already shows delivery address separately when relevant; this just adds "whose phone number is this."
- Deduplicating/merging customers across phone-number variants (e.g. with/without country code) — the stored phone is compared as submitted, post-validation, no normalization beyond what the existing WhatsApp-number regex already implies.

## Decisions

**`customers` as a simple table**, not a child of `businesses` in the `product_ingredients`/`payment_methods` sense (those are business-owner-authored lists; a `Customer` is created by system behavior in response to an anonymous public order, not authored by the owner). Columns: `id`, `business_id` (FK cascade), `phone` (`varchar`), `created_at`. A composite unique index on `(business_id, phone)` enforces per-business phone uniqueness at the DB layer, not just in application logic — the same defense-in-depth reasoning already applied to `orders.status_id`'s `RESTRICT` FK in the prior `add-order-status` change.

**Find-or-create via `INSERT ... ON CONFLICT (business_id, phone) DO UPDATE SET id = customers.id RETURNING id`** (a Postgres upsert-returning-id idiom), not a separate `SELECT` followed by conditional `INSERT` — the two-step version has the same race window as the order-numbering problem solved in `add-order-status` (two concurrent first-orders from the same new phone could both pass the `SELECT`-finds-nothing check before either commits an `INSERT`). One atomic statement avoids that without needing a row lock.

**Phone validation lives in `OrderService`**, reusing `BusinessService`'s regex as a shared constant/method (extract it from `BusinessService` into a small shared place both can reference — mirrors how `add-order-status` extracted `OrderStatus::defaults()` to avoid duplicating the fulfillment-status seed data; the same "don't duplicate the literal" lesson applies to this regex) rather than copy-pasting the pattern a second time.

**`Order.customerId`**: nullable at the schema level (existing pre-change orders have none), but always populated by `OrderService::create` going forward — the "required, no exceptions" rule from the spec is enforced in application validation (reject the order if phone is missing/invalid), not by a `NOT NULL` constraint, since a `NOT NULL` column can't distinguish "this is an old order that predates the feature" from "someone tried to bypass the requirement." `order-history`'s response represents a null customer as `null`/absent phone (should only ever appear on pre-change historical orders).

**Client-remembered phone**: a small `localStorage`-backed helper mirroring `DeliveryLocationService`'s `load()`/`persist()` try/catch shape, under its own key (e.g. `vendaly.customer-phone`), separate from the cart and delivery-location keys. Populated into the checkout form on load, always editable, always re-sent as whatever's currently in the field (not silently reused from storage without being shown).

## Risks / Trade-offs

- [A customer types a slightly different phone number each time (typo, extra space) and ends up with multiple `Customer` rows at the same business] → Accepted for this version; no normalization/fuzzy-matching is in scope. The regex still bounds the format enough to keep entries roughly consistent, and this mirrors how the existing WhatsApp-number field has always worked (stored as submitted, once validated).
- [Requiring a phone number with zero exceptions could block a customer without one handy at checkout] → This is an explicit, deliberate product decision from the user ("obligatorio... sin excepciones" was not literally said but "obligatorio" was, and the motivation — knowing who ordered — only holds if it's actually mandatory); no bypass is provided.
- [The `(business_id, phone)` unique index adds a small write-path constraint check] → Negligible at this app's scale; consistent with other unique constraints already in the schema (e.g. slug uniqueness).

## Migration Plan

Additive: new `customers` table with its unique index, and a nullable `customer_id` on `orders`. No backfill needed — historical orders simply have no linked customer (there's no real phone number to retroactively attribute to them), consistent with how `add-order-fulfillment`'s delivery fields were left null for pre-existing orders. Rollback is dropping the new table/column; no existing behavior depends on them being present.
