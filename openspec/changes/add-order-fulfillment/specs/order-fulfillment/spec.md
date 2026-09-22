# Spec Delta

## Purpose

Lets a business declare how it fulfills orders (pickup, delivery, dine-in) and lets a customer pick one of those methods, with delivery details, before an order is sent.

## ADDED Requirements

### Requirement: Fulfillment method configuration
The system SHALL allow the business owner to enable or disable each of three fixed fulfillment methods — `pickup`, `delivery`, `dine_in` — for their business, and to set an optional fixed fee for each (defaulting to none/zero). The system SHALL always keep at least one method enabled: a request that would leave zero methods enabled is rejected.

#### Scenario: Enable a method
- **WHEN** the business owner enables `delivery` for their business
- **THEN** the system persists it as enabled and it becomes selectable by customers on the public catalog

#### Scenario: Enable a method with a fee
- **WHEN** the business owner enables `delivery` with a fee of 30
- **THEN** the system persists the fee, and a customer selecting `delivery` sees that fee added to their total

#### Scenario: Enable a method with no fee
- **WHEN** the business owner enables a method without setting a fee
- **THEN** the system treats it as having no fee, and it adds nothing to the customer's total

#### Scenario: Disable a method while others remain enabled
- **WHEN** the business owner disables `dine_in` while `pickup` and `delivery` are still enabled
- **THEN** the system persists the change

#### Scenario: Attempt to disable the last enabled method
- **WHEN** the business owner attempts to disable the only currently-enabled method
- **THEN** the system rejects the request with a validation error, and the method remains enabled

### Requirement: Delivery details capture
The system SHALL require, when a customer selects `delivery` as their fulfillment method, at least one of: a free-text delivery address, or a map-pinned latitude/longitude. Both may be provided together; neither is individually mandatory, but at least one is required.

#### Scenario: Delivery with address only
- **WHEN** a customer selects delivery and submits a free-text address with no map pin
- **THEN** the system accepts the order

#### Scenario: Delivery with pin only
- **WHEN** a customer selects delivery and submits map coordinates with no address text
- **THEN** the system accepts the order

#### Scenario: Delivery with neither
- **WHEN** a customer selects delivery and submits neither an address nor coordinates
- **THEN** the system rejects the order without creating it

### Requirement: Customer fulfillment selection validated against enabled methods
The system SHALL require the customer to select exactly one fulfillment method when submitting an order, and SHALL reject the order if the selected method is not currently enabled for that business.

#### Scenario: Select an enabled method
- **WHEN** a customer submits an order selecting `pickup`, and the business currently has `pickup` enabled
- **THEN** the system accepts the order and records the selected method

#### Scenario: Select a disabled or unknown method
- **WHEN** a customer submits an order selecting a fulfillment method the business does not currently have enabled
- **THEN** the system rejects the order without creating it

#### Scenario: No fulfillment method selected
- **WHEN** a customer submits an order without selecting a fulfillment method
- **THEN** the system rejects the order without creating it

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
