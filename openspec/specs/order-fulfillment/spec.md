## Purpose

Lets a business declare how it fulfills orders (pickup, delivery, dine-in) and lets a customer pick one of those methods, with delivery details, before an order is sent.

## Requirements

### Requirement: Fulfillment method configuration (dynamic list)
The system SHALL allow the business owner to add, rename, and delete named fulfillment methods for their business, each with an optional fixed fee (defaulting to none/zero) and a `requires_address` flag (defaulting to false). Every new business SHALL be seeded with one default fulfillment method named "Consumo en el local" (`requires_address = false`, no fee). The system SHALL always keep at least one fulfillment method: a request that would delete the last remaining one is rejected.

#### Scenario: Default seeded on business creation
- **WHEN** a new business is created
- **THEN** the system creates one fulfillment method for it named "Consumo en el local", with `requires_address` false and no fee

#### Scenario: Add a fulfillment method
- **WHEN** the business owner adds a fulfillment method named "Envío foráneo" with a fee of 50 and `requires_address` true
- **THEN** the system persists it and it becomes selectable by customers on the public catalog

#### Scenario: Add a fulfillment method with no fee
- **WHEN** the business owner adds a fulfillment method without setting a fee
- **THEN** the system treats it as having no fee, and it adds nothing to a customer's total when selected

#### Scenario: Delete a fulfillment method while others remain
- **WHEN** the business owner deletes a fulfillment method while at least one other remains
- **THEN** the system persists the deletion

#### Scenario: Attempt to delete the last remaining fulfillment method
- **WHEN** the business owner attempts to delete their only remaining fulfillment method
- **THEN** the system rejects the request with a validation error, and the method remains

### Requirement: Delivery details capture (by requires_address flag)
The system SHALL require, when a customer selects a fulfillment method whose `requires_address` flag is true, at least one of: a free-text delivery address, or a map-pinned latitude/longitude. Both may be provided together; neither is individually mandatory, but at least one is required. A method whose `requires_address` flag is false SHALL NOT require or prompt for either.

#### Scenario: Address-requiring method with address only
- **WHEN** a customer selects a fulfillment method with `requires_address` true and submits a free-text address with no map pin
- **THEN** the system accepts the order

#### Scenario: Address-requiring method with pin only
- **WHEN** a customer selects a fulfillment method with `requires_address` true and submits map coordinates with no address text
- **THEN** the system accepts the order

#### Scenario: Address-requiring method with neither
- **WHEN** a customer selects a fulfillment method with `requires_address` true and submits neither an address nor coordinates
- **THEN** the system rejects the order without creating it

#### Scenario: Method that does not require an address
- **WHEN** a customer selects a fulfillment method with `requires_address` false
- **THEN** the system accepts the order regardless of whether an address or coordinates were submitted

### Requirement: Customer fulfillment selection validated by id
The system SHALL require the customer to select exactly one of the business's currently-configured fulfillment methods (by id) when submitting an order, and SHALL reject the order if the selected method does not currently belong to that business.

#### Scenario: Select a configured method
- **WHEN** a customer submits an order selecting a fulfillment method that currently belongs to the business
- **THEN** the system accepts the order and records the selected method's id, name, and fee

#### Scenario: Select an unavailable or unknown method
- **WHEN** a customer submits an order selecting a fulfillment method id that does not currently belong to that business
- **THEN** the system rejects the order without creating it

#### Scenario: No fulfillment method selected
- **WHEN** a customer submits an order without selecting a fulfillment method
- **THEN** the system rejects the order without creating it

### Requirement: Fulfillment method recorded on the order
The system SHALL persist the selected fulfillment method's name and fee on the created order (snapshotted at creation time, independent of any later rename/fee change/deletion), and the generated WhatsApp message SHALL state the selected method using its snapshotted name.

#### Scenario: Fulfillment method survives a later rename
- **WHEN** the business owner renames a fulfillment method after an order that used it was created
- **THEN** that order's recorded fulfillment method name is unchanged

#### Scenario: Fulfillment method survives a later deletion
- **WHEN** the business owner deletes a fulfillment method after an order that used it was created
- **THEN** that order's recorded fulfillment method name and fee are unchanged

### Requirement: Client-remembered delivery location
The system SHALL remember the customer's last-submitted delivery address and/or map pin in the browser only (not on the server, and not tied to any account), and SHALL prefill the delivery form with it on a subsequent visit from the same browser.

#### Scenario: Prefill from a previous delivery order
- **WHEN** a customer who previously submitted a delivery order with an address and/or pin opens the delivery form again in the same browser
- **THEN** the form is prefilled with that previously-submitted address/pin, editable before submitting

#### Scenario: No previous delivery order in this browser
- **WHEN** a customer opens the delivery form in a browser with no previously-remembered address/pin
- **THEN** the form starts empty, requiring the customer to provide one

### Requirement: Two-step checkout
The system SHALL split the customer-facing order flow into two steps: a cart-review step (items, quantities, notes) with no order-submission action, and a checkout step that shows the items subtotal, the fulfillment-method selector (with each enabled method's fee, if any), the resulting fee, the grand total (subtotal plus fee), and the only "Enviar por WhatsApp" action in the flow. Delivery address/pin capture and payment-method selection (see `payment-methods`) happen on the checkout step, not the cart-review step.

#### Scenario: Cart-review step has no submit action
- **WHEN** a customer is on the cart-review step
- **THEN** they can adjust items, quantities, and notes, and continue to the checkout step, but cannot submit the order from this step

#### Scenario: Checkout step shows the fee-adjusted total
- **WHEN** a customer on the checkout step selects a fulfillment method with a non-zero fee
- **THEN** the displayed total updates to subtotal plus that fee, and the fee is shown as its own line separate from the items subtotal

#### Scenario: Checkout step shows no fee line for a fee-less method
- **WHEN** a customer on the checkout step selects a fulfillment method with no fee
- **THEN** no fee line is shown, and the total equals the subtotal
