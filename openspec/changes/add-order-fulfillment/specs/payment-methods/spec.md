# Spec Delta

## Purpose

Lets a business maintain the list of payment methods it accepts (named by the owner, not a fixed set) and lets a customer pick one at checkout.

## ADDED Requirements

### Requirement: Payment method management
The system SHALL allow the business owner to add, rename, reorder, and delete named payment methods for their business. Every new business SHALL be seeded with one default payment method named "Efectivo". The system SHALL always keep at least one payment method: a request that would delete the last remaining one is rejected.

#### Scenario: Default seeded on business creation
- **WHEN** a new business is created
- **THEN** the system creates one payment method for it named "Efectivo"

#### Scenario: Add a payment method
- **WHEN** the business owner adds a payment method named "Transferencia"
- **THEN** the system persists it and it becomes selectable by customers on the public catalog

#### Scenario: Delete a payment method while others remain
- **WHEN** the business owner deletes a payment method while at least one other remains
- **THEN** the system persists the deletion

#### Scenario: Attempt to delete the last remaining payment method
- **WHEN** the business owner attempts to delete their only remaining payment method
- **THEN** the system rejects the request with a validation error, and the method remains

### Requirement: Customer payment method selection
The system SHALL require the customer to select exactly one of the business's currently-available payment methods when submitting an order, on the checkout step (see `order-fulfillment`'s two-step checkout requirement), and SHALL reject the order if the selected payment method does not currently belong to that business.

#### Scenario: Select an available payment method
- **WHEN** a customer submits an order selecting a payment method that currently belongs to the business
- **THEN** the system accepts the order and records the selected payment method

#### Scenario: Select an unavailable or unknown payment method
- **WHEN** a customer submits an order selecting a payment method that does not currently belong to that business
- **THEN** the system rejects the order without creating it

#### Scenario: No payment method selected
- **WHEN** a customer submits an order without selecting a payment method
- **THEN** the system rejects the order without creating it

### Requirement: Payment method recorded on the order
The system SHALL persist the selected payment method's name on the created order (snapshotted at creation time, independent of any later rename/deletion), and the generated WhatsApp message SHALL state the selected payment method.

#### Scenario: Payment method survives a later rename
- **WHEN** the business owner renames a payment method after an order that used it was created
- **THEN** that order's recorded payment method name is unchanged
