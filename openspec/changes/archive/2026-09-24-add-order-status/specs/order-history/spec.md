# Spec Delta

## MODIFIED Requirements

### Requirement: Business owner order listing
The system SHALL allow the authenticated business owner to list their own business's orders, each including its creation date, business-scoped order number, current status (name and color), items (name, quantity, note), and total.

#### Scenario: List orders for a business with history
- **WHEN** the business owner requests their order list
- **THEN** the system returns all orders belonging to that business, most recent first, with their items, order number, and current status

#### Scenario: Non-owner cannot list another business's orders
- **WHEN** an authenticated user who is not a member of the business requests its order list
- **THEN** the system rejects the request with a forbidden error, using the same ownership guard as business/catalog management

## ADDED Requirements

### Requirement: Business owner updates an order's status
The system SHALL allow the business owner to change the status of one of their business's orders, subject to the validation rules in `order-status`.

#### Scenario: Update an order's status
- **WHEN** the business owner changes an order's status to one of their business's currently-defined statuses
- **THEN** the system persists the change, and it appears on the next order list request

#### Scenario: Non-owner cannot change another business's order status
- **WHEN** an authenticated user who is not a member of the business attempts to change one of its orders' status
- **THEN** the system rejects the request with a forbidden error
