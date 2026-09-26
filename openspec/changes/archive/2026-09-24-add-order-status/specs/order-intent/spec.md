# Spec Delta

## MODIFIED Requirements

### Requirement: Order persistence before WhatsApp handoff
The system SHALL expose `POST /public/orders`, which persists the customer's selection (items, quantities, notes, computed total) against the target business's catalog before the customer is redirected to WhatsApp. The system SHALL assign the created order a sequential number scoped to that business (starting at 1 for each business's first order, independent of any other business's numbering) and the business's current default status (see `order-status`). Persisting an order SHALL NOT imply payment, confirmation, or acceptance by the business.

#### Scenario: Successful order creation
- **WHEN** a customer submits a non-empty selection for a published catalog's slug
- **THEN** the system creates an `Order` with its `OrderItem`s, a business-scoped sequential order number, and the business's default status, and returns data sufficient to build the WhatsApp message

#### Scenario: Order numbers are independent per business
- **WHEN** two different businesses each receive their first order
- **THEN** each order is numbered 1, independently of the other business's numbering

#### Scenario: Empty selection
- **WHEN** a customer submits an order with no items
- **THEN** the system rejects the request without creating an `Order`

#### Scenario: Slug not published
- **WHEN** a customer submits an order against a slug that is not currently published
- **THEN** the system rejects the request

### Requirement: WhatsApp deep link generation
The system SHALL generate a `wa.me` deep link to the business's configured WhatsApp number, pre-filled with a formatted message listing the order number, each ordered item (quantity, name, price if present, note if present), and the order total.

#### Scenario: Message includes the order number
- **WHEN** an order is created
- **THEN** the generated message states the order's business-scoped order number

#### Scenario: Message formatting with priced items
- **WHEN** an order contains only priced items
- **THEN** the generated message lists each item as quantity, name, and price, followed by the total

#### Scenario: Message formatting with priceless items
- **WHEN** an order contains one or more priceless items
- **THEN** the generated message lists those items without a price and excludes them from the displayed total

#### Scenario: Business has no WhatsApp number configured
- **WHEN** an order is created for a business that has not configured a WhatsApp number
- **THEN** the system rejects the order creation with an error indicating the business cannot currently receive orders
