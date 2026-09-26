# Tasks

## 1. Backend: schema and shared validation

- [x] 1.1 Write a Cycle migration adding a `customers` table (`id BIGSERIAL PRIMARY KEY, business_id BIGINT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE, phone VARCHAR(32) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT now()`) with a unique index on `(business_id, phone)`, and a nullable `customer_id BIGINT NULL REFERENCES customers(id) ON DELETE SET NULL` on `orders`. Verify with `docker exec docker-api-1 php bin/migrate.php` and confirm the table/index/column exist via `psql \d customers` / `\d orders`.
- [x] 1.2 Extract the phone-format regex currently inline in `BusinessService` (`^\+?[1-9][0-9 ()-]{6,20}$`) into one shared place both `BusinessService` and the new customer-resolution code can reference (a small constant or static method — read `apps/api/src/Domain/Service/BusinessService.php` first to place it consistently with the codebase's style), and update `BusinessService` to use it instead of its inline pattern. Add/adjust a Unit test confirming `BusinessService`'s WhatsApp-number validation still behaves identically after the extraction.

## 2. Backend: customer entity, repository, and resolution

- [x] 2.1 Add a `Customer` Cycle entity (`apps/api/src/Domain/Entity/Customer.php`: id, businessId, phone, createdAt), non-final, annotation-based, matching existing entity conventions.
- [x] 2.2 Add a `CustomerRepositoryInterface` and its Cycle implementation with a `findOrCreateByPhone(int $businessId, string $phone): Customer` method implemented as a single atomic `INSERT ... ON CONFLICT (business_id, phone) DO UPDATE SET id = customers.id RETURNING id` (or equivalent upsert), per design.md — not a separate SELECT-then-INSERT. Add a Codeception Unit/Functional test asserting: a new phone creates one customer; a repeat phone at the same business reuses it (no duplicate row); the same phone at two different businesses produces two independent customers.
- [x] 2.3 Wire the new repository interface/implementation binding into `apps/api/config/common/di/application.php`, mirroring how `PaymentMethodRepositoryInterface`/`ProductOptionRepositoryInterface` are already bound.

## 3. Backend: order creation and history

- [x] 3.1 Extend `OrderService::create`'s input validation to require a `phone` field, validating it with the shared regex from task 1.2, rejecting the whole order (no `Order` or `Customer` created) if missing or malformed — mirror the existing all-or-nothing validation style already used for fulfillment/payment method validation in this file. Add Unit tests: missing phone rejected; malformed phone rejected; valid phone accepted.
- [x] 3.2 On successful validation, resolve the customer via `CustomerRepositoryInterface::findOrCreateByPhone` and assign the result's id to the new `Order`'s `customerId`, inside the same transaction `CycleOrderRepository::createWithItems` already opens (read that method — extended twice already in prior sessions for order-number/status — before adding a third responsibility to it; keep the pattern consistent with how those were added). Add a Unit test asserting a created order's `customerId` points at the resolved customer, and that two orders from the same phone at the same business share one customer.
- [x] 3.3 Extend `OrderController::map()` to include the customer's phone number in each listed order (join/lookup via the order's `customerId`; batch this lookup across all listed orders rather than querying per order, matching the existing batched patterns in `CatalogService`/`ProductService` for ingredients/options — avoid an N+1). Add a Codeception Functional test asserting the order list response includes each order's customer phone.

## 4. Frontend: checkout phone field

- [x] 4.1 Add a small `localStorage`-backed service (e.g. `apps/web/src/app/public/customer-phone.service.ts`) mirroring `DeliveryLocationService`'s `load()`/`persist()` try/catch pattern, under its own key (e.g. `vendaly.customer-phone`). Add a spec covering: persists on write, hydrates on construction, safe no-op if `localStorage` throws.
- [x] 4.2 Add a required phone-number field to `order-checkout.component.ts`/`.html` (read the current file first — it already hosts the fulfillment-method, delivery, and payment-method fields from prior sessions), prefilled from the new service on load, persisted via the same service on successful submit (mirroring how `DeliveryLocationService.persist()` is only called after a successful order in that same component). Extend `canSubmit()` to require a non-empty phone (basic client-side check; the server is authoritative on format). Verify by running the app: the field is required, blocks submit when empty, and prefills on a second visit.
- [x] 4.3 Update the order payload builder (`order-payload.ts` or wherever the `POST /public/orders` body is assembled — read `apps/web/src/app/public/order-api.service.ts`/`order-payload.ts` first) to include the phone number. Add/update `order-payload.spec.ts` covering the new field.

## 5. Frontend: order history

- [x] 5.1 Extend `apps/web/src/app/dashboard/order.models.ts`'s order shape with a `customer_phone` (or similar) field, and display it per order in `dashboard/order-history/order-history.component.ts`/`.html` (read the current file first — it already shows order number and status badge from a prior session; add the phone alongside those, not as a separate disconnected element).
- [x] 5.2 Add/update `order-history.component.spec.ts` covering: the customer phone renders per order; an order with no linked customer (a historical, pre-change order) renders without error (no phone, not a broken/empty-string display).

## 6. Verification

- [ ] 6.1 Run the full backend suite (`docker exec docker-api-1 vendor/bin/codecept run Unit` and `Functional`) and confirm no regressions, including all prior sessions' fulfillment/payment-method/product-option/order-status tests (re-run since `OrderService`/`OrderController`/`CycleOrderRepository`/`BusinessService` all change again).
- [ ] 6.2 Run the full frontend suite (`npm test -- --watch=false --browsers=ChromeHeadless` from the host, in `apps/web`) and confirm no regressions.
- [ ] 6.3 Manually exercise the end-to-end flow in the browser: attempt to submit an order at checkout with the phone field empty and confirm it's blocked; submit with a valid phone and confirm the order is created and the WhatsApp handoff still works; reload the checkout page for a new order and confirm the phone is prefilled from the previous one; in the dashboard, confirm the order appears in order-history with that phone number; place a second order with the same phone at the same business and confirm order-history shows both linked to what should be the same underlying customer (no visible duplicate-customer symptom, even though the UI doesn't show a raw customer id).
