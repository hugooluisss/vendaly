## MODIFIED Requirements

### Requirement: Business profile configuration
The system SHALL allow the business owner to set and update the business's name, logo, WhatsApp number, description, category, and location.

#### Scenario: Update profile fields
- **WHEN** the business owner submits updated name, logo, WhatsApp number, description, category, and/or location
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
