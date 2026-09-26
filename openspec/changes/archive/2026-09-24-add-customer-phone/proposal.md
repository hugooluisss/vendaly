# Proposal

## Why

Right now an order carries no identity for who placed it — no name, no phone, nothing the business can use to contact or recognize a repeat customer. The business owner has no way to answer "who ordered this?" from the order-history view alone. This adds a mandatory phone number at checkout and a lightweight per-business customer record, so every order is traceable to a contactable person.

## What Changes

- The checkout step (where fulfillment method and payment method are already selected, before "Enviar por WhatsApp") gains a required phone-number field. No order can be submitted without it.
- On order creation, the system finds an existing `Customer` for that business with that phone number, or creates one if none exists. Customers are strictly per-business — the same phone number ordering from two different businesses produces two independent `Customer` records; businesses never see each other's customers.
- The created order is linked to the resolved `Customer`.
- The phone is validated with the same format rule `BusinessService` already applies to a business's own `whatsapp_number`.
- The phone number is remembered client-side only (`localStorage`, same pattern as the existing delivery-location memory) to prefill the field on a future order from the same browser. It is not a login or account — there's no way for a customer to see their own order history, and no password/session tied to it.
- The order-history view gains the customer's phone number per order, so the business owner can identify/contact whoever ordered.

## Capabilities

### New Capabilities
- `customer-records`: per-business, phone-identified customer records, resolved (find-or-create) at order time.

### Modified Capabilities
- `order-intent`: order creation requires a valid phone number and links the created order to the resolved per-business customer.
- `order-history`: the order list response includes the customer's phone number.

## Impact

- **Backend**: a new `customers` table (id, business_id, phone, created_at), unique per (business_id, phone); `Order` gains a `customer_id` FK; `OrderService` resolves (find-or-create) the customer before/during order creation and validates the phone format (reusing `BusinessService`'s existing regex); `OrderController`'s list mapping includes the customer's phone.
- **Frontend**: the checkout component (`order-checkout`) gains a required phone input, validated client-side (basic non-empty/format check, server is authoritative) and blocking submit until filled; a small `localStorage`-backed helper (mirroring `DeliveryLocationService`) remembers the last-used phone; `dashboard/order-history` displays the customer's phone per order.
- **No breaking changes**: this only affects new orders going forward; existing orders have no customer (see design.md for how they're displayed — a nullable customer reference, not a backfilled fake one, since there's no real phone number to attribute to historical orders).
