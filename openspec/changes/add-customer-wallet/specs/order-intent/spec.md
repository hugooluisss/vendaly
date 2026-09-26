# Spec Delta

## MODIFIED Requirements

### Requirement: Order persistence before WhatsApp handoff
The system SHALL expose `POST /public/orders`, which persists the customer's selection (items, quantities, notes, selected option values, computed subtotal), a required phone number, the selected fulfillment method (with its fee at the time of order, if any), the selected payment method, and — when the fulfillment method requires it — the delivery address and/or coordinates, against the target business's catalog before the customer is redirected to WhatsApp. The system resolves the phone number to a per-business `Customer` record (see `customer-records`) and links the order to it; assigns the order a sequential number scoped to that business (starting at 1 for each business's first order, independent of any other business's numbering) and the business's current default status (see `order-status`); and validates each item's selected option values server-side (a `required` single-select group must have exactly one selected value from its own values, and a `multiple` group's selected values must all belong to that group). The order's total SHALL be the items subtotal plus the fulfillment method's fee (zero if none), minus any wallet amount redeemed (see `customer-wallet`). Persisting an order SHALL NOT imply payment, confirmation, or acceptance by the business. See `order-fulfillment` for fulfillment-method and delivery-detail validation rules, `payment-methods` for payment-method validation rules, and `customer-wallet` for wallet accrual/redemption rules.

#### Scenario: Successful order creation
- **WHEN** a customer submits a non-empty selection, a valid phone number, a valid fulfillment method, a valid payment method, and (when required) an address and/or coordinates, for a published catalog's slug
- **THEN** the system creates an `Order` with its `OrderItem`s, linked to the resolved `Customer`, with a business-scoped sequential order number, the business's default status, the selected fulfillment method and its fee, the selected payment method, and any delivery details, with a total equal to the subtotal plus the fee, and returns data sufficient to build the WhatsApp message

#### Scenario: Order numbers are independent per business
- **WHEN** two different businesses each receive their first order
- **THEN** each order is numbered 1, independently of the other business's numbering

#### Scenario: Total includes a non-zero fulfillment fee
- **WHEN** a customer's order has an items subtotal of 100 and selects a fulfillment method with a fee of 30
- **THEN** the persisted order total is 130, with the subtotal and fee individually available to build the WhatsApp message

#### Scenario: Missing phone number
- **WHEN** a customer submits an order without a phone number
- **THEN** the system rejects the request without creating an `Order` or a `Customer`

#### Scenario: Empty selection
- **WHEN** a customer submits an order with no items
- **THEN** the system rejects the request without creating an `Order`

#### Scenario: Slug not published
- **WHEN** a customer submits an order against a slug that is not currently published
- **THEN** the system rejects the request

#### Scenario: Missing required option selection
- **WHEN** a customer submits an order item that omits a value for a `required` single-select group on that product
- **THEN** the system rejects the request without creating an `Order`

#### Scenario: Option value from a different product or group
- **WHEN** a customer submits an order item selecting an option value that does not belong to one of that product's own option groups
- **THEN** the system rejects the request without creating an `Order`

#### Scenario: Total reduced by a wallet redemption
- **WHEN** a customer submits an order with a wallet redemption amount, for a business with wallet enabled and a customer with sufficient balance
- **THEN** the persisted order total is the subtotal plus fee minus the redeemed amount

### Requirement: WhatsApp deep link generation
The system SHALL generate a `wa.me` deep link to the business's configured WhatsApp number, pre-filled with a formatted message listing the order number, each ordered item (quantity, name, selected option values, price if present, note if present), the selected fulfillment method and its fee when non-zero, delivery details when applicable, the selected payment method, the subtotal/fee/total breakdown, and — when the business has wallet enabled — the wallet amount credited by this order, any amount redeemed, and the customer's resulting wallet balance.

#### Scenario: Message includes the order number
- **WHEN** an order is created
- **THEN** the generated message states the order's business-scoped order number

#### Scenario: Message formatting with priced items
- **WHEN** an order contains only priced items
- **THEN** the generated message lists each item as quantity, name, and price, followed by the total

#### Scenario: Message formatting with priceless items
- **WHEN** an order contains one or more priceless items
- **THEN** the generated message lists those items without a price and excludes them from the displayed total

#### Scenario: Message formatting with selected options
- **WHEN** an order item has one or more selected option values
- **THEN** the generated message lists those values alongside the item, and the item's displayed price (when present) reflects their `price_delta`s

#### Scenario: Message includes fulfillment method
- **WHEN** an order has a selected fulfillment method
- **THEN** the generated message states the chosen method in a human-readable form

#### Scenario: Message includes delivery details
- **WHEN** an order's fulfillment method requires an address
- **THEN** the generated message includes the delivery address and/or a map link built from the coordinates, whichever was provided

#### Scenario: Message includes a non-zero fulfillment fee
- **WHEN** an order's fulfillment method has a non-zero fee
- **THEN** the generated message shows a subtotal, the fee as its own line, and the total, in place of a single combined total line

#### Scenario: Message includes the payment method
- **WHEN** an order has a selected payment method
- **THEN** the generated message states the selected payment method

#### Scenario: Business has no WhatsApp number configured
- **WHEN** an order is created for a business that has not configured a WhatsApp number
- **THEN** the system rejects the order creation with an error indicating the business cannot currently receive orders

#### Scenario: Message includes wallet balance when enabled
- **WHEN** an order is created for a business with wallet enabled
- **THEN** the generated message states the amount credited to the customer's wallet by this order and the customer's resulting wallet balance

#### Scenario: Message omits wallet details when disabled
- **WHEN** an order is created for a business with wallet disabled
- **THEN** the generated message contains no wallet-related lines
