# Proposal

## Why

Today a customer's order carries no information about how the business should fulfill it — the WhatsApp handoff just lists items and a total, leaving pickup/delivery/dine-in logistics to be worked out entirely inside the WhatsApp chat. Businesses that only do pickup, or that need a delivery address up front, have no way to express that, and customers have no structured way to provide it. This adds a fulfillment method step to the existing order flow: the business owner declares which of pickup, delivery, and dine-in they support, and the customer picks one (providing an address/pin for delivery) before the order reaches WhatsApp.

## What Changes

- Business owners configure which of three fixed fulfillment methods (`pickup`, `delivery`, `dine_in`) their business currently supports, each with an optional fixed fee; at least one must always remain enabled — the system rejects a request that would disable the last one, and rejects publishing a catalog with none enabled.
- Business owners maintain an open, named list of payment methods they accept (e.g. "Efectivo", "Tarjeta", "Transferencia") — not a fixed set, the owner names their own, the same pattern as product ingredients. Every new business is seeded with one default entry, "Efectivo". At least one payment method must always remain: the system rejects deleting the last one, and rejects publishing a catalog with none.
- The public catalog response exposes the business's currently-enabled fulfillment methods (with fees) and its payment methods, so the customer's checkout screen knows what to offer.
- The customer-facing flow becomes two steps: the existing cart/items review screen (`/order`) no longer submits the order — it only reviews items/quantities/notes and continues to a new checkout step. That checkout step shows the items subtotal, the fulfillment-method selector (with each option's fee, if any), the payment-method selector, the resulting fee and grand total (subtotal + fulfillment fee), and — only here — the "Enviar por WhatsApp" button. Selecting `delivery` requires providing a free-text address and/or a map pin (reusing the existing shared Leaflet/OpenStreetMap component already used for business location) — at least one of the two.
- The customer's last-used delivery address/pin is remembered client-side only (`localStorage`), to prefill the form on a future order from the same browser; it is never persisted server-side as a "default" tied to any account (there are no customer accounts in this app).
- `POST /public/orders` persists the selected fulfillment method (and its fee at order time), the selected payment method, and — for delivery — the address/coordinates, validated against the business's currently-enabled/available methods.
- The generated WhatsApp message includes the fulfillment method, its fee when non-zero, the delivery address/coordinates when applicable, the payment method, and the subtotal/fee/total breakdown.

## Capabilities

### New Capabilities
- `order-fulfillment`: business-owner configuration of enabled fulfillment methods (with optional fees), and customer-side selection and delivery-detail capture at order time.
- `payment-methods`: business-owner management of a named, ordered list of accepted payment methods (seeded with "Efectivo"), and customer-side selection at order time.

### Modified Capabilities
- `catalog-management`: the "Catalog publish/unpublish" requirement gains preconditions that at least one fulfillment method and at least one payment method are configured.
- `public-catalog`: the public catalog lookup response must include the business's enabled fulfillment methods (with fees) and its payment methods.
- `order-intent`: order persistence must capture the selected fulfillment method (with fee), payment method, and delivery details; WhatsApp message generation must include them and the subtotal/fee/total breakdown. The customer flow's WhatsApp submission moves from the cart-review screen to a new checkout step.

## Impact

- **Backend**: fulfillment-method columns on `businesses` gain a fee per method; a new `payment_methods` child table (mirroring `product_ingredients`: id, business_id, name, position) with a seed row on business creation; `BusinessService` validation extended to payment methods (can't delete the last one, can't publish with none); `Order` gains fulfillment fee snapshot, payment-method id + name snapshot; `OrderService` validates the submitted payment method against the business's current list; `WhatsAppOrderLink` includes the new fields and the fee/total breakdown.
- **Frontend**: `dashboard/business-settings` gains fee inputs per fulfillment method and a payment-methods list editor (add/remove/reorder, mirroring the existing ingredient-list editor pattern); the public order flow splits into the existing `/order` (cart review, no submit) and a new checkout route/step (fulfillment + payment selection, fee/total breakdown, delivery address/pin, the WhatsApp submit button); `cart`/order payload building carries the new selections.
- **No breaking changes** to businesses that predate this change — see design.md for the default-enabled-methods/seeded-payment-method approach for existing businesses so none of them end up unable to publish the moment this ships.
