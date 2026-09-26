# Proposal

## Why

Businesses want a way to reward repeat customers without building full loyalty/points infrastructure or touching payments. A per-business wallet — credited automatically from a per-product amount when an order is placed, reversed if the order is cancelled/rejected, and redeemable against a later order's total — gives them that, using the phone-number identity the catalog already has (`customer-records`) without adding customer login or cross-business data sharing.

## What Changes

- Add a `wallet_enabled` toggle on `Business` (owner-configurable, default off) that gates all wallet behavior for that business.
- Add an optional `wallet_amount` field per `Product` (a fixed currency amount, not a percentage) — the amount credited to the customer's wallet per unit of that product sold, when wallet is enabled.
- Add a `WalletTransaction` ledger (accrual, reversal, redemption) keyed by `Customer` (already business-scoped, never shared across businesses) — the wallet balance is the sum of that customer's transactions, never a mutable counter, so every change stays auditable.
- On `POST /public/orders`, when the business has wallet enabled: accrue `sum(product.wallet_amount * quantity)` across the order's items as a `credit` transaction, and return the customer's resulting wallet balance so it can be shown to the customer and included in the WhatsApp message.
- Allow the same request to redeem part of the existing wallet balance against the order: accept an optional redemption amount (capped at the customer's current balance and at the order's subtotal), record it as a `debit` transaction, and reduce the total communicated to the business accordingly.
- Add a `reverses_wallet` flag on `OrderStatus` (alongside the existing `is_terminal`/`is_default` flags) so a business can mark which of its terminal statuses represent cancellation/rejection. Seed the default "Cancelado" status with `reverses_wallet = true`.
- When an order's status changes to one flagged `reverses_wallet`, post an offsetting `reversal` transaction for that order's original `credit` (and, if it had one, reissue the `debit` amount as a `credit` so a redeemed-then-cancelled order restores the spent balance) — the original transactions are never edited or deleted.
- Wallet transactions only ever apply once per order (accrual on creation, at most one reversal on a `reverses_wallet` transition) — re-entering the same `reverses_wallet` status, or changing to a second `reverses_wallet` status, does not post a second reversal.

## Capabilities

### New Capabilities
- `customer-wallet`: per-business, per-customer wallet balance derived from an auditable transaction ledger; accrual from product wallet amounts on order creation, redemption against an order's total, and reversal on order cancellation/rejection.

### Modified Capabilities
- `catalog-management`: `Product` creation/edit gains an optional `wallet_amount` field.
- `business-management`: `Business` profile configuration gains a `wallet_enabled` toggle.
- `order-status`: adds a `reverses_wallet` flag to `OrderStatus`, alongside `is_terminal`/`is_default`, and updates the seeded "Cancelado" status to set it.
- `order-intent`: `POST /public/orders` accrues wallet credit and accepts wallet redemption when the business has wallet enabled; the WhatsApp message and order-creation response communicate the resulting wallet balance and any redemption applied.

## Impact

- **Backend**: new `WalletTransaction` entity/table/repository/migration; `Business.walletEnabled` and `Product.walletAmount` columns; `OrderStatus.reversesWallet` column; a `WalletService` (or extension of `OrderService`) invoked from order creation and `OrderController::changeStatus`; `OrderController`/`OrderService::create` response payload gains wallet balance/redemption fields.
- **Frontend**: catalog management product form gains a wallet-amount input; business settings gain a wallet-enabled toggle; public order summary/checkout shows accrued balance and offers redemption when available.
- **No payments/POS impact**: redemption only reduces the total communicated to the business via WhatsApp; no money is actually moved or charged by the system.
