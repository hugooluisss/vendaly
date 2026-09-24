# Spec Delta

## MODIFIED Requirements

### Requirement: Business profile configuration
The system SHALL allow the business owner to set and update the business's name, logo, cover image, WhatsApp number, description, category, location, coordinates (latitude/longitude), and Facebook, Instagram, and website URLs.

#### Scenario: Update profile fields
- **WHEN** the business owner submits updated name, logo, cover image, WhatsApp number, description, category, location, coordinates, and/or Facebook, Instagram, or website URLs
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

#### Scenario: Facebook, Instagram, and website URLs are optional
- **WHEN** the business owner updates their profile without submitting a Facebook URL, Instagram URL, or website URL
- **THEN** the system leaves any unsubmitted one of those fields unset, and the business remains valid and manageable

#### Scenario: Set a Facebook, Instagram, or website URL
- **WHEN** the business owner submits a well-formed URL for the Facebook URL, Instagram URL, or website URL field
- **THEN** the system persists it against that field

#### Scenario: Reject a malformed social or website URL
- **WHEN** the business owner submits a value for the Facebook URL, Instagram URL, or website URL field that is not a well-formed URL
- **THEN** the system rejects the update and returns a validation error identifying the field

#### Scenario: Clear a previously set social or website URL
- **WHEN** the business owner submits an empty value for a Facebook URL, Instagram URL, or website URL field that previously had a value
- **THEN** the system unsets that field
