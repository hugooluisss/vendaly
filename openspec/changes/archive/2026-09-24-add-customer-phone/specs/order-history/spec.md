# Spec Delta

## MODIFIED Requirements

### Requirement: Business owner order listing
The system SHALL allow the authenticated business owner to list their own business's orders, each including its creation date, items (name, quantity, note), total, and the ordering customer's phone number.

#### Scenario: List orders for a business with history
- **WHEN** the business owner requests their order list
- **THEN** the system returns all orders belonging to that business, most recent first, with their items and the customer's phone number

#### Scenario: Non-owner cannot list another business's orders
- **WHEN** an authenticated user who is not a member of the business requests its order list
- **THEN** the system rejects the request with a forbidden error, using the same ownership guard as business/catalog management
