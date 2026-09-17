## Purpose

Lets an authenticated user create and configure the business they represent — its identity, contact channel, and schedule — as the foundation the catalog and public page build on.

## Requirements

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
The system SHALL allow the business owner to set and update the business's name, logo, cover image, WhatsApp number, description, category, location, and coordinates (latitude/longitude).

#### Scenario: Update profile fields
- **WHEN** the business owner submits updated name, logo, cover image, WhatsApp number, description, category, location, and/or coordinates
- **THEN** the system persists the changes and they are reflected on subsequent reads

#### Scenario: Invalid WhatsApp number format
- **WHEN** the business owner submits a WhatsApp number that is not a valid phone number
- **THEN** the system rejects the update and returns a validation error identifying the field

#### Scenario: Set a category from the fixed list
- **WHEN** the business owner submits a category value from the supported list (Restaurante, Café, Salón de belleza, Servicios profesionales, Tienda, Reparaciones, Otro)
- **THEN** the system persists it as the business's category

#### Scenario: Reject an unsupported category value
- **WHEN** the business owner submits a category value that is not in the supported list
- **THEN** the system rejects the update and returns a validation error identifying the field

#### Scenario: Category and location are optional
- **WHEN** the business owner updates their profile without submitting a category or location
- **THEN** the system leaves those fields unset, and the business remains valid and manageable

#### Scenario: Coordinates are optional
- **WHEN** the business owner updates their profile without submitting coordinates
- **THEN** the system leaves latitude/longitude unset, and the business remains valid and manageable

#### Scenario: Set coordinates by pinning a location
- **WHEN** the business owner places a pin on the map and submits the resulting latitude/longitude
- **THEN** the system persists both coordinates together (never one without the other)

#### Scenario: Reject an out-of-range coordinate
- **WHEN** the business owner submits a latitude outside [-90, 90] or a longitude outside [-180, 180]
- **THEN** the system rejects the update and returns a validation error identifying the field

#### Scenario: Upload a cover image
- **WHEN** the business owner uploads a cover image, distinct from their logo
- **THEN** the system stores it and associates it with the business as its `cover_image_url`, replacing any previous cover image

#### Scenario: Cover image is optional
- **WHEN** a business has no cover image set
- **THEN** the business remains valid and manageable, and the public catalog falls back to a plain header instead of a cover photo

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
