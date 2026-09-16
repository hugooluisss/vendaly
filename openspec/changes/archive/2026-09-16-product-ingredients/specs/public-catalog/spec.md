## MODIFIED Requirements

### Requirement: Public catalog lookup by slug
The system SHALL expose `GET /public/catalog/:slug` without requiring authentication, returning the business's profile, opening hours, categories, and active products (each with its ingredient list, when any exist) when the catalog is published.

#### Scenario: Published catalog
- **WHEN** a visitor requests a slug belonging to a published catalog
- **THEN** the system returns the business info, opening hours, categories, and only active products, each including its ingredients if it has any

#### Scenario: Unpublished or unknown slug
- **WHEN** a visitor requests a slug that does not exist or belongs to an unpublished catalog
- **THEN** the system returns a not-found response without revealing whether the business exists but is unpublished

#### Scenario: Product without ingredients
- **WHEN** a published catalog includes a product that has no ingredients configured
- **THEN** the public response represents that product with an empty ingredient list rather than omitting the field
