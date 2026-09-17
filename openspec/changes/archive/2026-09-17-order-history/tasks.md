## 1. Backend

- [x] 1.1 Add `OrderRepository::findByBusinessId(businessId, ?from, ?to)` (SQL-level date filtering, joins `OrderItem`) and a count variant (or derive count from the same filtered query); verify a unit test covers no-range, custom-range, and confirms orders outside the range are excluded
- [x] 1.2 Add `OrderService::listForBusiness(userId, businessId, ?from, ?to)` using `BusinessMemberGuard::assertOwner()` before querying; verify a unit test asserts a non-member gets a forbidden error
- [x] 1.3 Add authenticated route + controller action (e.g. `GET /businesses/{id}/orders`) returning `{ orders: [...], count: <int> }`; verify a test hits the real endpoint with `from`/`to` query params and confirms the shape and filtering

## 2. Frontend

- [x] 2.1 Add an `OrderApiService` (or extend an existing one) with a `list(businessId, from?, to?)` call matching the backend response shape
- [x] 2.2 Add a dashboard "Pedidos" screen/nav entry: date-range control (today / 7 days / 30 days / custom), order count, and a list of orders (date, items, note, total); reuse existing dashboard layout/design tokens
- [x] 2.3 Wire the new nav entry into the dashboard sidebar

## 3. Verification

- [x] 3.1 Run the full backend test suite inside docker-api-1 and the Angular build/tests, confirm both green
- [x] 3.2 Manual smoke test: create a couple of orders via `POST /public/orders` against a real published catalog, then confirm they show up (and are correctly counted) in the new dashboard screen for the matching date range
