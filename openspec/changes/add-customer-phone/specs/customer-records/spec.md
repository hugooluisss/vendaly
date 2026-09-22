# Spec Delta

## Purpose

Lets each business build up its own record of who has ordered from them, identified by phone number, without any customer-facing login or cross-business sharing.

## ADDED Requirements

### Requirement: Per-business customer resolution by phone
The system SHALL, given a business and a phone number, return the existing `Customer` for that business with that phone number if one exists, or create a new one. Customers SHALL be scoped strictly to one business: the same phone number used at two different businesses SHALL produce two independent `Customer` records, and a business SHALL never be able to read another business's customer records.

#### Scenario: First order from a new phone number
- **WHEN** a customer places an order with a phone number that business has not seen before
- **THEN** the system creates a new `Customer` for that business with that phone number

#### Scenario: Repeat order from the same phone number
- **WHEN** a customer places a second order at the same business using the same phone number
- **THEN** the system reuses the existing `Customer` record rather than creating a duplicate

#### Scenario: Same phone number at two different businesses
- **WHEN** the same phone number is used to order from business A and business B
- **THEN** the system creates or resolves two separate `Customer` records, one per business, with no shared data between them

### Requirement: Phone number format validation
The system SHALL validate a submitted phone number against the same format rule used for a business's own WhatsApp number, rejecting the order if it doesn't match.

#### Scenario: Valid phone number
- **WHEN** a customer submits a phone number matching the accepted format
- **THEN** the system accepts it and proceeds with customer resolution

#### Scenario: Invalid phone number
- **WHEN** a customer submits a phone number that doesn't match the accepted format
- **THEN** the system rejects the order without creating it or any `Customer` record
