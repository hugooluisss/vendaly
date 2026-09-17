## Purpose

Lets a business owner see how many orders their catalog produced over a given period, and inspect the individual orders, without touching order creation or the WhatsApp handoff.

## ADDED Requirements

### Requirement: Business owner order listing
The system SHALL allow the authenticated business owner to list their own business's orders, each including its creation date, items (name, quantity, note), and total.

#### Scenario: List orders for a business with history
- **WHEN** the business owner requests their order list
- **THEN** the system returns all orders belonging to that business, most recent first, with their items

#### Scenario: Non-owner cannot list another business's orders
- **WHEN** an authenticated user who is not a member of the business requests its order list
- **THEN** the system rejects the request with a forbidden error, using the same ownership guard as business/catalog management

### Requirement: Date range filtering
The system SHALL allow the business owner to filter the order list by a date range (`from`, `to`), returning only orders created within that range (inclusive).

#### Scenario: Filter to a custom range
- **WHEN** the business owner requests orders with a `from`/`to` date range
- **THEN** the system returns only orders whose creation date falls within that range

#### Scenario: No range provided
- **WHEN** the business owner requests the order list without a date range
- **THEN** the system returns all of the business's orders, unfiltered

### Requirement: Order count for a range
The system SHALL return, alongside the (possibly paginated) order list, the total count of orders matching the requested filter.

#### Scenario: Count matches filtered results
- **WHEN** the business owner requests orders for a date range
- **THEN** the returned count reflects exactly how many orders fall within that range, independent of how many are included in the returned list
