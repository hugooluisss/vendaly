# Spec Delta

## MODIFIED Requirements

### Requirement: Business profile configuration
The system SHALL allow the business owner to set and update the business's name, logo, cover image, WhatsApp number, description, category, location, coordinates (latitude/longitude), and whether the customer wallet tool is enabled (`wallet_enabled`, default off).

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

#### Scenario: Enable the wallet tool
- **WHEN** the business owner turns on `wallet_enabled`
- **THEN** the system persists it, and subsequent orders for that business accrue wallet credit (see `customer-wallet`)

#### Scenario: Wallet disabled by default
- **WHEN** a business is created
- **THEN** `wallet_enabled` defaults to off, and orders for that business do not accrue or affect any wallet until the owner turns it on

#### Scenario: Disable the wallet tool
- **WHEN** the business owner turns off `wallet_enabled` for a business with existing customer wallet balances
- **THEN** the system stops accruing or redeeming wallet credit for new orders, but existing wallet balances and transaction history are preserved unchanged
