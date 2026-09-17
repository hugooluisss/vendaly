## Context

`Order`/`OrderItem` and their repository already exist (`order-intent`), with `Order.created_at` set at creation. `BusinessMemberGuard` already enforces "only the owning member can manage this business" for business/catalog endpoints — this change reuses it verbatim rather than inventing a second authorization mechanism.

## Goals / Non-Goals

**Goals:**
- Give the owner a read-only list + count of their orders, filterable by date.
- Reuse existing entities/guard; no new tables.

**Non-Goals:**
- No order status/lifecycle (confirmed, cancelled, fulfilled) — out of scope per the proposal, matches the existing principle that an order is an intent, not a sale.
- No pagination UI polish beyond what's needed to not load unbounded history in one response (see Decisions) — this is a first cut, not a full reporting dashboard.
- No cross-business analytics or comparisons — one business owner sees only their own business.

## Decisions

- **New `OrderService::listForBusiness(userId, businessId, ?from, ?to)`** method, following the same `guard->assertOwner()` + repository call pattern already used by `CategoryService`/`ProductService`, rather than a new standalone service — order listing is a query concern that belongs next to `OrderService::create()`.
- **Repository method `OrderRepository::findByBusinessId(businessId, ?from, ?to)`** doing the date filtering in SQL (`WHERE business_id = ? AND created_at BETWEEN ? AND ?` when both bounds given), not in PHP after fetching everything — avoids loading unbounded history into memory for businesses with a long order history.
- **Response shape**: `{ orders: [...], count: <int> }`, matching the established `{ <plural>: [...] }` wrapper convention already used by categories/products, with `count` as a sibling field (not derived by the frontend from `orders.length`, since a future pagination limit would make those diverge — count must reflect the full filtered set).
- **Simple date-range query params** (`from`, `to` as `YYYY-MM-DD`) rather than a more general filter/query language — matches the MVP's preset-ranges-plus-custom-range UI need from the proposal, nothing more.
- **No pagination in this version**: given order volume is expected to be low for an MVP business, return the full filtered set. Flagged as a risk below rather than built now — YAGNI until a real business shows this doesn't scale.

## Risks / Trade-offs

- **No pagination** → a business with very high order volume over a wide date range could return a large payload. Mitigation: the date-range filter itself bounds this in the common case (owners checking "this week"/"today"); add pagination if a real business's history grows large enough to matter, not preemptively.
- **`OrderItem` has no direct `businessId` column** (it's reached via `order_id` → `orders.business_id`) — the repository query must join through `Order`, not attempt a shortcut; this matches how `OrderService::create()` already persists items under their order.
