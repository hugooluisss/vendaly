# Proposal

## Why

Orders already persist to the database before the WhatsApp handoff (`OrderService::create` creates the `Order`/`OrderItem`s, then `WhatsAppOrderLink::generate` builds the message from the already-created order), so the "is a record kept" question is already answered — yes. What's missing is everything a business needs to actually *work* that record: a number the customer and business can both reference (today only a raw database `id`, shared globally across every business on the platform, appears nowhere in the customer-facing message), and a status so the owner can track an order from "just came in" through to "delivered" or "cancelled" in their own order history. Without these, `order-history` is a read-only list with no way to act on it.

## What Changes

- Every order gets a per-business sequential order number (e.g. business A's orders are #1, #2, #3…; business B's are independently #1, #2, #3…) — distinct from the internal database `id`.
- The generated WhatsApp message includes the order number.
- Business owners maintain an open, named list of order statuses, each with a color and two flags: `is_terminal` and `is_default`. Every new business is seeded with four: "Creado" (default), "Elaborando", "Entregado" (terminal), "Cancelado" (terminal). The owner can add, rename, recolor, reorder, and delete statuses — mirroring the existing payment-methods pattern: at least one status must always remain, the current default can't be deleted without a new one being designated first, and a status currently assigned to any order can't be deleted.
- Every new order is assigned the business's current default status at creation time.
- The business owner can change an individual order's status, picking among their business's currently-configured statuses, from the order-history view.
- The order-history response gains each order's number and current status (name + color).

## Capabilities

### New Capabilities
- `order-status`: business-owner management of a named, colored, ordered list of order statuses (with terminal/default flags), and the assignment/update of an order's status.

### Modified Capabilities
- `order-intent`: order creation assigns a per-business sequential order number and the business's default status; the generated WhatsApp message includes the order number.
- `order-history`: the order list response includes each order's number and current status; the business owner can update an order's status.

## Impact

- **Backend**: `Order` gains a `businessOrderNumber` (or similar) column, computed per-business at creation time (see design.md for the concurrency-safe approach), and a `status_id`/status snapshot; a new `order_statuses` child table (mirroring `payment_methods`: id, business_id, name, color, is_terminal, is_default, position) with a seed of four rows on business creation; `BusinessService`-style CRUD + guards for the status list; `OrderService` assigns the default status on creation; `WhatsAppOrderLink` includes the order number; `OrderController`/order-history endpoint gains a status-update action and includes number/status in its response.
- **Frontend**: `dashboard/order-history` gains a status column/badge (colored) with a way to change it, and displays the order number; a new (or extended existing) settings section for managing the status list (add/rename/recolor/reorder/delete, set default/terminal), likely alongside the existing payment-methods editor in `dashboard/business-settings`.
- **No breaking changes**: existing orders (created before this change) have no status; see design.md for how they're handled (backfill vs. nullable "unassigned" display) and no order number (see design.md for the backfill approach so order-history doesn't show blank numbers for historical rows).
