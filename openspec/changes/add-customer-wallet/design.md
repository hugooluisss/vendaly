# Design

## Context

See `proposal.md` - Why. Relevant existing shape:

- `Customer` (`customers` table) is already business-scoped by `(business_id, phone)` unique key, resolved via `CycleCustomerRepository::findOrCreateByPhone` inside `CycleOrderRepository::createWithItems`'s transaction. The wallet's identity key is this same `Customer`, so no new identity concept is introduced.
- `OrderStatus` is fully business-defined (name/color/`is_terminal`/`is_default` are all owner-editable, not a fixed enum) — there is no built-in "cancelled" vs "completed" distinction beyond what the business names its statuses. `Order::changeStatus()` in `OrderService` just re-points `order.status_id` at any of the business's own statuses.
- `OrderService::create()` builds the order, computes `total` in integer cents internally, then persists via `OrderRepositoryInterface::createWithItems()`, which runs everything (customer resolution, order-number increment, default-status lookup, order + items + item-options) inside one driver transaction.
- `Product` and `Business` are plain Cycle entities with scalar columns; no existing pattern for money-like optional fields beyond `decimal, nullable`.

## Goals / Non-Goals

**Goals:**
- Add wallet accrual/redemption/reversal as a bounded extension to the existing order-creation and status-change paths, reusing the existing transaction boundary in `CycleOrderRepository::createWithItems` rather than introducing a second commit phase.
- Keep the wallet ledger append-only and independently reconstructable (balance = `SUM(amount)` over a customer's transactions), so a bug in one transaction never corrupts a stored running balance.

**Non-Goals:**
- No customer login/account system. Identity stays phone + business, exactly as `customer-records` already defines it.
- No real payment/settlement. Redemption only changes the number communicated to the business via WhatsApp; the system never moves money.
- No wallet-to-wallet transfer, expiry, or multi-business sharing (explicitly ruled out by the proposal).
- No admin UI for manually adjusting a customer's wallet in this change — only accrual, order-time redemption, and automatic reversal.

## Decisions

**1. Ledger table (`wallet_transactions`), not a mutable balance column.**
A `WalletTransaction` row per event (`credit` on order creation, `debit` on redemption, `reversal` to undo either) with `customer_id`, `order_id` (nullable — only order-driven transactions have one), `type`, `amount` (decimal, always stored positive; sign is implied by `type`), `created_at`. Balance is computed as `SUM(CASE WHEN type IN ('credit') THEN amount WHEN type = 'reversal' THEN -amount * sign_of_original ... END)` — see below for the simpler encoding actually used.
- *Alternative considered*: a `wallet_balance` counter column on `Customer`, updated in place. Rejected because the reversal requirement ("movimiento contrario", never edit/delete the original) explicitly asks for auditability, and a counter can drift from reality under a retried/partially-failed request in a way a ledger can't.

**1a. Transaction sign encoding.** Store `amount` as a signed decimal (positive = adds to balance, negative = subtracts), with `type` kept only as a semantic label (`credit`, `debit`, `reversal`) for display/audit, not for sign computation. Balance = `SUM(amount)`. A reversal of a `credit` of 20 is a `reversal` row of `-20`; a reversal that restores a redeemed `debit` of 15 is a `reversal` row of `+15`. This keeps balance computation a single `SUM`, with no per-type branching in SQL.
- *Alternative considered*: unsigned amounts with type-based sign lookup in every query. Rejected as more error-prone (every reader must re-derive the sign rule) for no real benefit.

**2. One ledger entry per order per direction, found by `order_id` + `type`, not a separate "wallet order state" table.**
Reversal logic queries `wallet_transactions WHERE order_id = ? AND type IN ('credit','debit')` for the *originating* entries, and checks whether a `reversal` already references that same `order_id` (any reversal row for that order means "already reversed", satisfying the idempotency requirement) before posting a new one.
- *Alternative considered*: a boolean `wallet_reversed` flag on `Order`. Rejected — it duplicates state the ledger already encodes, and this repo already avoids that kind of denormalization elsewhere (e.g. status is a real FK, not a string flag).

**3. `reverses_wallet` as a new boolean column on `OrderStatus`, sibling to `is_terminal`/`is_default`.**
This matches the existing pattern exactly (owner manages a small set of booleans per status) and lets a business flag whichever of its own custom statuses represents "cancelled" or "rejected" without the system guessing from status names. Reversal is triggered from `OrderService::changeStatus()` after the status FK is updated, by checking the target status's `reverses_wallet` flag.
- *Alternative considered*: hardcode reversal to the seeded "Cancelado" status by name. Rejected — statuses are fully owner-editable/renameable per `order-status`, so name-matching is fragile and breaks for a business that renames or adds a "Rechazado" status.

**4. Wallet accrual/redemption computed inside `OrderService::create()`, persisted inside `CycleOrderRepository::createWithItems()`'s existing transaction.**
`OrderService` computes: (a) the order's total credit = `Σ product.wallet_amount * quantity` for wallet-enabled businesses, (b) validates any requested redemption against the *pre-order* balance and the order's subtotal-plus-fee, and (c) passes both to the repository alongside the order/items/options it already builds. The repository posts the `credit`/`debit` `WalletTransaction` rows in the same driver transaction as the order insert, so an order and its wallet effect are atomic (both succeed or both roll back) — this reuses the transaction boundary that already exists for order+items+options rather than adding a second phase or a saga.
- *Alternative considered*: a separate `WalletService::accrueForOrder()` called after `createWithItems()` commits. Rejected for this change because it reintroduces the atomicity problem the ledger design is trying to avoid (an order could persist with no matching wallet effect on a later failure). A `WalletService` can still exist as where the *pure computation* (redemption validation, balance lookup) lives — it's just invoked from within `OrderService`/`OrderRepositoryInterface`'s flow, not as an independent post-commit step.

**5. Redemption validated against balance computed from the ledger at request time, inside the same transaction, not optimistic-locked.**
Given this app's traffic profile (one business, WhatsApp handoff, no concurrent-cart contention modeled anywhere else in the codebase), a `SELECT SUM(amount) ... FOR UPDATE`-style read-then-write inside the existing transaction is sufficient. No new concurrency-control primitive is introduced.
- *Alternative considered*: application-level optimistic locking (version column) on a computed balance. Rejected as unnecessary complexity for a single-business, single-counter contention case with no other locking present in `OrderService`.

**6. Wallet fields are additive/nullable; no behavior change when `wallet_enabled` is false.**
`Business.wallet_enabled` (boolean, default false) and `Product.wallet_amount` (nullable decimal) follow the exact nullable-optional-field convention already used for `Product.price` and `Business.latitude`/`longitude`. All wallet logic in `OrderService`/`CycleOrderRepository` is skipped entirely when the business's flag is off, so existing non-wallet businesses see zero behavior change (matches the "already-published business unaffected" pattern used elsewhere in `catalog-management`).

## Risks / Trade-offs

- **[Risk]** Ledger-based balance requires summing rows on every read (order creation, redemption validation, order list display). At this app's expected order volume (single small business, no batch/enterprise usage in scope) this is not a performance concern → **Mitigation**: none needed now; if it ever matters, add an index on `wallet_transactions(customer_id)` (included in the migration) before considering a cached-balance column.
- **[Risk]** A business could rename/repurpose a `reverses_wallet` status in a way that surprises customers (e.g. flipping it on for a status many existing orders already sit in) → **Mitigation**: reversal only fires on a *status change event* (`OrderService::changeStatus()`), never retroactively for orders already sitting in a status when the flag is toggled — this matches how `is_default`/`is_terminal` already behave (flag changes are forward-only).
- **[Risk]** Redemption amount and wallet balance are read and written outside of Cycle ORM's entity change-tracking (raw ledger rows, similar to how `order_number`/`status_id` defaults are already handled with raw SQL in `CycleOrderRepository`) → **Mitigation**: keep all wallet reads/writes inside the same driver transaction and same repository class, consistent with the existing raw-SQL sections of that file.

## Migration Plan

1. Migration: add `wallet_enabled BOOLEAN NOT NULL DEFAULT FALSE` to `businesses`; add `wallet_amount DECIMAL NULL` to `products`; add `reverses_wallet BOOLEAN NOT NULL DEFAULT FALSE` to `order_statuses`; backfill existing seeded "Cancelado" rows to `reverses_wallet = TRUE` (mirrors how `OrderStatusSeedRepair` backfills existing businesses today); create `wallet_transactions` table (`id`, `customer_id` FK → `customers.id` ON DELETE CASCADE, `order_id` FK → `orders.id` ON DELETE SET NULL nullable, `type` VARCHAR, `amount` DECIMAL signed, `created_at`), with an index on `customer_id`.
2. No data backfill needed for existing wallet transactions (feature is opt-in and starts at zero balance for every customer).
3. Rollback: drop `wallet_transactions`; drop the three added columns — safe because nothing else depends on them yet (mirrors the `down()` pattern already used in every migration in `apps/api/migrations/`).

## Open Questions

None — the redemption UX (shown as an optional amount field at checkout, applied atomically with order creation) and the reversal trigger (status-flag-driven, not name-matching) are both resolved above.
