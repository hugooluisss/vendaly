# Tasks

## 1. Backend data model

- [x] 1.1 Add migration `wallet_enabled BOOLEAN NOT NULL DEFAULT FALSE` on `businesses`, `wallet_amount DECIMAL NULL` on `products`, `reverses_wallet BOOLEAN NOT NULL DEFAULT FALSE` on `order_statuses` (backfilling existing seeded "Cancelado" rows to `TRUE`, mirroring `OrderStatusSeedRepair`), and a new `wallet_transactions` table (`id`, `customer_id` FK → `customers.id` ON DELETE CASCADE, `order_id` FK → `orders.id` ON DELETE SET NULL nullable, `type` VARCHAR, `amount` signed DECIMAL, `created_at`, index on `customer_id`) with a working `down()`; verify with `docker exec vendaly-api-1 php bin/migrate.php` running clean against dev Postgres
- [x] 1.2 Add `walletEnabled` to `Business` entity, `walletAmount` to `Product` entity, `reversesWallet` to `OrderStatus` entity (and its `defaults()` seed array), and a new `WalletTransaction` entity in `src/Domain/Entity/`; verify Cycle ORM schema compiles (`docker exec vendaly-api-1 vendor/bin/codecept run Unit` boots without mapping errors)

## 2. Wallet repository and computation

- [x] 2.1 Add `WalletRepositoryInterface` (`balanceForCustomer(int $customerId): string`, `postTransaction(...)`, `hasReversalForOrder(int $orderId): bool`, `originalTransactionsForOrder(int $orderId): array`) in `src/Domain/Repository/` and its Cycle implementation in `src/Infrastructure/Cycle/Repository/`; register in `config/common/di/application.php` next to the other repository bindings; verify with a new `tests/Unit` test asserting balance = sum of posted transactions
- [x] 2.2 Implement redemption validation (amount ≤ current balance, amount ≤ subtotal-plus-fee) as a pure function/method reachable from `OrderService`; verify with unit tests for the three redemption scenarios in `customer-wallet` spec (reduces total, rejects over-balance, rejects over-total)

## 3. Order creation integration

- [x] 3.1 In `OrderService::create()`, when the business has `wallet_enabled`, compute the order's wallet credit as `Σ product.wallet_amount * quantity` and validate/apply any requested redemption against the resolved customer's current balance; pass both through to the repository; verify with `tests/Unit` covering: accrual computed correctly, zero accrual when no product has a wallet amount, redemption reduces persisted `Order.total`
- [x] 3.2 In `CycleOrderRepository::createWithItems()`, post the `credit`/`debit` `WalletTransaction` rows inside the same driver transaction as the order/items/options insert, referencing the created order; verify with a test that a forced failure after order insert (e.g. invalid item) leaves no wallet transaction persisted (atomicity)
- [x] 3.3 Extend the `POST /public/orders` request/response contract: accept an optional redemption amount in the request body, and include the customer's resulting wallet balance (and amount credited/redeemed) in the response and in the `OrderService::create()` return array; verify with a `tests/Unit`/functional test asserting the response shape for a wallet-enabled business

## 4. Reversal on status change

- [x] 4.1 In `OrderService::changeStatus()`, after persisting the new `status_id`, check whether the target status has `reverses_wallet` true and no reversal already exists for that order (via `WalletRepositoryInterface::hasReversalForOrder`); if so, post a `reversal` transaction offsetting the order's original `credit` (and, if present, a `credit` restoring its `debit`); verify with unit tests for: cancelling a credited order reverses it, cancelling a redeeming order restores the redeemed amount, repeated/second `reverses_wallet` transitions post no additional reversal, an order with no wallet activity posts nothing
- [x] 4.2 Confirm no reversal fires retroactively when a business toggles an existing status's `reverses_wallet` flag on/off (only a live status-change event triggers it); verify with a unit test that flipping the flag alone posts no transactions

## 5. WhatsApp message and API surface

- [x] 5.1 Update `WhatsAppOrderLink::generate()` to append wallet-credited amount and resulting balance (and any redeemed amount) when the business has wallet enabled, and to omit those lines entirely when disabled; verify with unit tests for both the "message includes wallet balance" and "message omits wallet details" scenarios
- [x] 5.2 Update `OrderController::create`/`map()` and any owner-facing order list/detail response to surface wallet fields (credited amount, redeemed amount) where relevant for the business's own order view; verify by inspecting the JSON response shape against `order-intent` spec scenarios

## 6. Business and catalog management endpoints

- [x] 6.1 Extend the business profile update endpoint/service to accept and persist `wallet_enabled`; verify with a unit test for enabling/disabling and the default-off scenario
- [x] 6.2 Extend product create/update endpoints/service to accept and validate `wallet_amount` (reject negative values, allow null); verify with unit tests for the three `catalog-management` wallet scenarios
- [x] 6.3 Extend the order-status management endpoint to accept and persist `reverses_wallet` per status, independent of `is_terminal`/`is_default`; verify with unit tests for the two `order-status` wallet scenarios

## 7. Frontend — business settings and catalog

- [x] 7.1 Add a wallet-enabled toggle to `apps/web/src/app/dashboard/business-settings/business-settings.component.{ts,html}`; verify by starting `npm start`, toggling it, and confirming the value persists on reload
- [x] 7.2 Add a `reverses_wallet` checkbox to `order-statuses-modal` alongside the existing terminal/default controls; verify manually that a status can be flagged and it persists
- [x] 7.3 Add a wallet-amount input to the product create/edit form in the catalog management screen; verify manually that saving a product with a wallet amount round-trips through reload

## 8. Frontend — public checkout

- [x] 8.1 In the public order summary/checkout flow, when the business has wallet enabled, show the customer's phone-resolved wallet balance (once known) and an optional redemption input capped at the current balance and order subtotal; verify manually in the browser against a wallet-enabled test business
- [x] 8.2 After order submission, display the resulting wallet balance and any amount credited/redeemed before redirecting to WhatsApp; verify manually that the WhatsApp-bound message and on-screen summary agree

## 9. End-to-end verification

- [x] 9.1 Run the full backend suite and confirm no regressions: `docker exec vendaly-api-1 vendor/bin/codecept run Unit`
- [ ] 9.2 Manually walk the golden path in the browser: enable wallet on a test business, set a wallet amount on a product, place an order, confirm balance shown and message includes it, cancel the order, confirm balance reverts to pre-order value
