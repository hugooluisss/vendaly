# Spec Delta

## MODIFIED Requirements

### Requirement: Cart/selection management on the client
The system SHALL allow a customer to add products to a selection, choose values for each of a product's option groups (respecting each group's `selection_type` and `required` flag), change item quantities, and remove items, entirely client-side, without requiring authentication. An item's line price is its unit price plus the sum of the `price_delta` of every selected option value, multiplied by quantity.

#### Scenario: Add item and change quantity
- **WHEN** a customer adds a product to their selection and then increases its quantity
- **THEN** the selection reflects the updated quantity and a recalculated subtotal for that item

#### Scenario: Selection includes priceless products
- **WHEN** a customer adds a product that has no price to their selection
- **THEN** the item appears in the selection and order summary without contributing to the computed total

#### Scenario: Add item with selected options
- **WHEN** a customer adds a product with an option group to their selection and selects one or more values
- **THEN** the item's line price includes the selected values' `price_delta`s, and the selection displays which values were chosen

#### Scenario: Required single-select group left unselected
- **WHEN** a customer tries to add a product to their selection without selecting a value for a `required` single-select group
- **THEN** the client blocks adding the item until a value is selected

### Requirement: Optional per-item notes
The system SHALL allow a customer to attach a free-text note to an item in their selection (e.g. "no onions").

#### Scenario: Note included in order
- **WHEN** a customer adds a note to an item and submits the order
- **THEN** the persisted order item includes that note and it appears in the generated WhatsApp message

### Requirement: Order persistence before WhatsApp handoff
The system SHALL expose `POST /public/orders`, which persists the customer's selection (items, quantities, notes, selected option values, computed total) against the target business's catalog before the customer is redirected to WhatsApp. Persisting an order SHALL NOT imply payment, confirmation, or acceptance by the business. The system SHALL validate each item's selected option values server-side against that product's current option groups: a `required` single-select group must have exactly one selected value from its own values, and a `multiple` group's selected values must all belong to that group.

#### Scenario: Successful order creation
- **WHEN** a customer submits a non-empty selection for a published catalog's slug
- **THEN** the system creates an `Order` with its `OrderItem`s and returns data sufficient to build the WhatsApp message

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

### Requirement: WhatsApp deep link generation
The system SHALL generate a `wa.me` deep link to the business's configured WhatsApp number, pre-filled with a formatted message listing each ordered item (quantity, name, selected option values, price if present, note if present) and the order total.

#### Scenario: Message formatting with priced items
- **WHEN** an order contains only priced items
- **THEN** the generated message lists each item as quantity, name, and price, followed by the total

#### Scenario: Message formatting with priceless items
- **WHEN** an order contains one or more priceless items
- **THEN** the generated message lists those items without a price and excludes them from the displayed total

#### Scenario: Message formatting with selected options
- **WHEN** an order item has one or more selected option values
- **THEN** the generated message lists those values alongside the item, and the item's displayed price (when present) reflects their `price_delta`s

#### Scenario: Business has no WhatsApp number configured
- **WHEN** an order is created for a business that has not configured a WhatsApp number
- **THEN** the system rejects the order creation with an error indicating the business cannot currently receive orders
