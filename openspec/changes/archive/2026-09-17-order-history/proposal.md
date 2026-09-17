## Why

Orders are already persisted before the WhatsApp handoff (see `order-intent`), but a business owner currently has no way to see them — there is no dashboard view of past orders and no way to answer "how many orders did I get this week?". This adds that visibility so owners can track demand over time, without touching how orders are created or sent to WhatsApp.

## What Changes

- A business owner can view a list of their business's orders (date, items, note, total) from the dashboard.
- The list can be filtered by a date range (presets: today / last 7 days / last 30 days, plus a custom range).
- The owner sees a count of orders for the selected range alongside the list.
- Read-only: this does not add order status, confirmation, cancellation, or any write action on an order — an order remains exactly what `order-intent` already defines (an intent captured before WhatsApp, not a confirmed sale).

## Capabilities

### New Capabilities
- `order-history`: authenticated business-owner read access to their own orders, filterable by date range, with a count.

### Modified Capabilities
(none — `order-intent`'s creation behavior is unchanged; this only adds a new read surface)

## Impact

- **Backend**: new authenticated endpoint (e.g. `GET /businesses/{id}/orders?from=&to=`) backed by `OrderRepository`, reusing the existing `Order`/`OrderItem` entities — no schema changes needed since `Order.created_at` already exists.
- **Frontend**: new dashboard screen (or a new tab under an existing one) listing orders for the selected range with the count, using `BusinessMemberGuard` the same way catalog/business screens already do.
- No change to the public-facing order creation flow, the WhatsApp message, or `POST /public/orders`.
