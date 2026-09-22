# Spec Delta

## MODIFIED Requirements

### Requirement: Order persistence before WhatsApp handoff
The system SHALL expose `POST /public/orders`, which persists the customer's selection (items, quantities, notes, computed subtotal), selected fulfillment method (with its fee at the time of order, if any), selected payment method, and — for delivery — the delivery address and/or coordinates, against the target business's catalog before the customer is redirected to WhatsApp. The order's total SHALL be the items subtotal plus the fulfillment method's fee (zero if none). Persisting an order SHALL NOT imply payment, confirmation, or acceptance by the business. See `order-fulfillment` for fulfillment-method and delivery-detail validation rules, and `payment-methods` for payment-method validation rules.

#### Scenario: Successful order creation
- **WHEN** a customer submits a non-empty selection, a valid fulfillment method, a valid payment method, and (for delivery) an address and/or coordinates, for a published catalog's slug
- **THEN** the system creates an `Order` with its `OrderItem`s, the selected fulfillment method and its fee, the selected payment method, and any delivery details, with a total equal to the subtotal plus the fee, and returns data sufficient to build the WhatsApp message

#### Scenario: Total includes a non-zero fulfillment fee
- **WHEN** a customer's order has an items subtotal of 100 and selects a fulfillment method with a fee of 30
- **THEN** the persisted order total is 130, with the subtotal and fee individually available to build the WhatsApp message

#### Scenario: Empty selection
- **WHEN** a customer submits an order with no items
- **THEN** the system rejects the request without creating an `Order`

#### Scenario: Slug not published
- **WHEN** a customer submits an order against a slug that is not currently published
- **THEN** the system rejects the request

### Requirement: WhatsApp deep link generation
The system SHALL generate a `wa.me` deep link to the business's configured WhatsApp number, pre-filled with a formatted message listing each ordered item (quantity, name, price if present, note if present), the selected fulfillment method and its fee when non-zero, delivery details when applicable, the selected payment method, and the subtotal/fee/total breakdown.

#### Scenario: Message formatting with priced items
- **WHEN** an order contains only priced items
- **THEN** the generated message lists each item as quantity, name, and price, followed by the total

#### Scenario: Message formatting with priceless items
- **WHEN** an order contains one or more priceless items
- **THEN** the generated message lists those items without a price and excludes them from the displayed total

#### Scenario: Message includes fulfillment method
- **WHEN** an order has a selected fulfillment method of `pickup`, `delivery`, or `dine_in`
- **THEN** the generated message states the chosen method in a human-readable form

#### Scenario: Message includes delivery details
- **WHEN** an order's fulfillment method is `delivery`
- **THEN** the generated message includes the delivery address and/or a map link built from the coordinates, whichever was provided

#### Scenario: Message includes a non-zero fulfillment fee
- **WHEN** an order's fulfillment method has a non-zero fee
- **THEN** the generated message shows the fee as its own line, separate from the items subtotal, followed by the total

#### Scenario: Message includes the payment method
- **WHEN** an order has a selected payment method
- **THEN** the generated message states the selected payment method

#### Scenario: Business has no WhatsApp number configured
- **WHEN** an order is created for a business that has not configured a WhatsApp number
- **THEN** the system rejects the order creation with an error indicating the business cannot currently receive orders
