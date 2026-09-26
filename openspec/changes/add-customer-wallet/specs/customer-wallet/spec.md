# Spec Delta

## Purpose

Lets a business reward a returning customer with a per-business, per-customer wallet balance — credited automatically from configured per-product amounts when an order is placed, redeemable against a later order's total, and reversed if the crediting order is cancelled or rejected — backed by an auditable transaction ledger rather than a mutable counter.

## ADDED Requirements

### Requirement: Wallet balance is derived from a transaction ledger
The system SHALL represent a customer's wallet balance as the sum of that customer's `WalletTransaction` records (never a directly-mutated counter). Each transaction has a type (`credit`, `debit`, or `reversal`), an amount, the `Customer` it belongs to, and — when it originates from an order — a reference to that `Order`. A customer's wallet is scoped to the business their `Customer` record belongs to and is never shared across businesses.

#### Scenario: Balance is the sum of transactions
- **WHEN** a customer has a `credit` of 50 and a `debit` of 20 recorded
- **THEN** the system reports their wallet balance as 30

#### Scenario: New customer has zero balance
- **WHEN** a customer has no wallet transactions yet
- **THEN** the system reports their wallet balance as 0

### Requirement: Wallet accrual on order creation
The system SHALL, when an order is created for a business with `wallet_enabled` true (see `business-management`), compute the order's wallet credit as the sum of each ordered item's `wallet_amount` (see `catalog-management`) multiplied by its quantity, and — if that sum is greater than zero — post a `credit` transaction of that amount for the order's resolved `Customer`, referencing the order. The system SHALL NOT post a credit transaction when the business has wallet disabled, or when the computed sum is zero.

#### Scenario: Order accrues wallet credit
- **WHEN** an order for a wallet-enabled business contains 2 units of a product with `wallet_amount` 5 and 1 unit of a product with `wallet_amount` 10
- **THEN** the system posts a `credit` transaction of 20 for the resolved customer, referencing that order

#### Scenario: No accrual when wallet disabled
- **WHEN** an order is created for a business with `wallet_enabled` false
- **THEN** the system posts no wallet transaction for that order, regardless of any product's `wallet_amount`

#### Scenario: No accrual when no ordered product has a wallet amount
- **WHEN** an order for a wallet-enabled business contains only products with no `wallet_amount` set
- **THEN** the system posts no `credit` transaction for that order

### Requirement: Wallet redemption against an order's total
The system SHALL allow a customer, when submitting an order to a wallet-enabled business, to request redeeming an amount from their current wallet balance against that order's total. The requested amount SHALL NOT exceed the customer's current wallet balance at the time of the order, nor the order's subtotal-plus-fee. When accepted, the system SHALL post a `debit` transaction for that amount, referencing the order, and SHALL subtract it from the order's total.

#### Scenario: Redemption reduces the order total
- **WHEN** a customer with a wallet balance of 40 redeems 15 against an order with subtotal-plus-fee of 100
- **THEN** the system posts a `debit` transaction of 15 referencing the order, and the order's total is persisted as 85

#### Scenario: Reject redemption exceeding balance
- **WHEN** a customer with a wallet balance of 10 requests to redeem 15
- **THEN** the system rejects the order request without creating an `Order` or any wallet transaction

#### Scenario: Reject redemption exceeding the order total
- **WHEN** a customer requests to redeem an amount greater than the order's subtotal-plus-fee
- **THEN** the system rejects the order request without creating an `Order` or any wallet transaction

#### Scenario: Redemption unavailable when wallet disabled
- **WHEN** a customer submits a redemption amount for an order at a business with `wallet_enabled` false
- **THEN** the system rejects the order request without creating an `Order` or any wallet transaction

### Requirement: Wallet reversal on order status change
The system SHALL, when an order's status is changed to one flagged `reverses_wallet` (see `order-status`), post a `reversal` transaction that offsets that order's original `credit` transaction (if any), and — if that order also had a `debit` transaction from a redemption — post a matching `credit` transaction restoring the redeemed amount. The system SHALL post at most one reversal per order: a second transition into a `reverses_wallet` status for the same order SHALL NOT post a second reversal. Reversal SHALL NOT edit or delete the order's original transactions.

#### Scenario: Cancelling a credited order reverses it
- **WHEN** an order that posted a `credit` of 20 transitions to a status with `reverses_wallet` true
- **THEN** the system posts a `reversal` transaction of -20 for that order's customer, and the original `credit` transaction remains unchanged in the ledger

#### Scenario: Cancelling an order that redeemed wallet balance restores it
- **WHEN** an order that posted a `debit` of 15 (from a redemption) transitions to a status with `reverses_wallet` true
- **THEN** the system posts a `credit` transaction of 15 restoring the redeemed amount, in addition to reversing the order's original accrual (if any)

#### Scenario: Reversal is idempotent per order
- **WHEN** an order already reversed once transitions again to the same or another `reverses_wallet` status
- **THEN** the system posts no additional reversal transaction for that order

#### Scenario: No reversal for an order with no wallet activity
- **WHEN** an order with no `credit` or `debit` transaction transitions to a `reverses_wallet` status
- **THEN** the system posts no wallet transaction for it

### Requirement: Wallet balance communicated to the customer
The system SHALL make the customer's resulting wallet balance available in the order-creation response so it can be shown to the customer and included in the WhatsApp handoff message (see `order-intent`), whenever the order was created for a wallet-enabled business.

#### Scenario: Balance returned after an order
- **WHEN** an order is successfully created for a wallet-enabled business
- **THEN** the response includes the customer's wallet balance after that order's accrual and any redemption
