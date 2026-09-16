## Purpose

Lets a customer build a selection of items from a public catalog, records that selection as an order intent before handoff, and produces the WhatsApp message/link that carries the conversation to the business.

## Requirements

### Requirement: Cart/selection management on the client
The system SHALL allow a customer to add products to a selection, change item quantities, and remove items, entirely client-side, without requiring authentication.

#### Scenario: Add item and change quantity
- **WHEN** a customer adds a product to their selection and then increases its quantity
- **THEN** the selection reflects the updated quantity and a recalculated subtotal for that item

#### Scenario: Selection includes priceless products
- **WHEN** a customer adds a product that has no price to their selection
- **THEN** the item appears in the selection and order summary without contributing to the computed total

### Requirement: Optional per-item notes
The system SHALL allow a customer to attach a free-text note to an item in their selection (e.g. "no onions").

#### Scenario: Note included in order
- **WHEN** a customer adds a note to an item and submits the order
- **THEN** the persisted order item includes that note and it appears in the generated WhatsApp message

### Requirement: Order persistence before WhatsApp handoff
The system SHALL expose `POST /public/orders`, which persists the customer's selection (items, quantities, notes, computed total) against the target business's catalog before the customer is redirected to WhatsApp. Persisting an order SHALL NOT imply payment, confirmation, or acceptance by the business.

#### Scenario: Successful order creation
- **WHEN** a customer submits a non-empty selection for a published catalog's slug
- **THEN** the system creates an `Order` with its `OrderItem`s and returns data sufficient to build the WhatsApp message

#### Scenario: Empty selection
- **WHEN** a customer submits an order with no items
- **THEN** the system rejects the request without creating an `Order`

#### Scenario: Slug not published
- **WHEN** a customer submits an order against a slug that is not currently published
- **THEN** the system rejects the request

### Requirement: WhatsApp deep link generation
The system SHALL generate a `wa.me` deep link to the business's configured WhatsApp number, pre-filled with a formatted message listing each ordered item (quantity, name, price if present, note if present) and the order total.

#### Scenario: Message formatting with priced items
- **WHEN** an order contains only priced items
- **THEN** the generated message lists each item as quantity, name, and price, followed by the total

#### Scenario: Message formatting with priceless items
- **WHEN** an order contains one or more priceless items
- **THEN** the generated message lists those items without a price and excludes them from the displayed total

#### Scenario: Business has no WhatsApp number configured
- **WHEN** an order is created for a business that has not configured a WhatsApp number
- **THEN** the system rejects the order creation with an error indicating the business cannot currently receive orders
