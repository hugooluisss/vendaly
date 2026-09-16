## Purpose

Lets an authenticated user create and configure the business they represent — its identity, contact channel, and schedule — as the foundation the catalog and public page build on.

## ADDED Requirements

### Requirement: Business creation
The system SHALL allow an authenticated user with no existing business to create one, generating a unique public slug derived from the business name.

#### Scenario: First business creation
- **WHEN** an authenticated user with no business submits a business name
- **THEN** the system creates a `Business`, links it to the user via `BusinessMember`, and generates a unique slug

#### Scenario: Slug collision
- **WHEN** the slug derived from the submitted business name already exists
- **THEN** the system generates a variant of the slug that is unique instead of rejecting the request

#### Scenario: User already owns a business
- **WHEN** an authenticated user who already has a business attempts to create another one
- **THEN** the system rejects the request (single business per user in this version)

### Requirement: Business profile configuration
The system SHALL allow the business owner to set and update the business's name, logo, WhatsApp number, and description.

#### Scenario: Update profile fields
- **WHEN** the business owner submits updated name, logo, WhatsApp number, and/or description
- **THEN** the system persists the changes and they are reflected on subsequent reads

#### Scenario: Invalid WhatsApp number format
- **WHEN** the business owner submits a WhatsApp number that is not a valid phone number
- **THEN** the system rejects the update and returns a validation error identifying the field

### Requirement: Opening hours configuration
The system SHALL allow the business owner to configure opening hours per day of the week, including marking a day as closed.

#### Scenario: Set weekly hours
- **WHEN** the business owner submits an open and close time for one or more days of the week
- **THEN** the system persists those hours for each specified day

#### Scenario: Mark a day closed
- **WHEN** the business owner marks a day of the week as closed
- **THEN** the system persists that day as closed regardless of any previously set hours

### Requirement: Only the owning member can manage a business
The system SHALL restrict business profile, hours, category, and product management to the user associated with that business via `BusinessMember`.

#### Scenario: User attempts to manage a business they don't own
- **WHEN** an authenticated user attempts to update, or manage the catalog of, a business they are not a member of
- **THEN** the system rejects the request with a forbidden error
