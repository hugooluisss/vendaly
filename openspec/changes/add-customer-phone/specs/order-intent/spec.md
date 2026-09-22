# Spec Delta

## MODIFIED Requirements

### Requirement: Order persistence before WhatsApp handoff
The system SHALL expose `POST /public/orders`, which persists the customer's selection (items, quantities, notes, computed total) against the target business's catalog before the customer is redirected to WhatsApp. The request SHALL include a phone number, required with no exceptions; the system resolves it to a per-business `Customer` record (see `customer-records`) and links the created order to it. Persisting an order SHALL NOT imply payment, confirmation, or acceptance by the business.

#### Scenario: Successful order creation
- **WHEN** a customer submits a non-empty selection and a valid phone number for a published catalog's slug
- **THEN** the system creates an `Order` with its `OrderItem`s, linked to the resolved `Customer`, and returns data sufficient to build the WhatsApp message

#### Scenario: Missing phone number
- **WHEN** a customer submits an order without a phone number
- **THEN** the system rejects the request without creating an `Order` or a `Customer`

#### Scenario: Empty selection
- **WHEN** a customer submits an order with no items
- **THEN** the system rejects the request without creating an `Order`

#### Scenario: Slug not published
- **WHEN** a customer submits an order against a slug that is not currently published
- **THEN** the system rejects the request
