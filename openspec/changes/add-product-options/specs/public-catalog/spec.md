# Spec Delta

## MODIFIED Requirements

### Requirement: Public catalog lookup by slug
The system SHALL expose `GET /public/catalog/:slug` without requiring authentication, returning the business's profile (including its cover image, when set), opening hours, categories, and active products (each with its ingredient list and option groups/values, when any exist) when the catalog is published.

#### Scenario: Published catalog
- **WHEN** a visitor requests a slug belonging to a published catalog
- **THEN** the system returns the business info (including its cover image if set), opening hours, categories, and only active products, each including its ingredients and option groups/values if it has any

#### Scenario: Unpublished or unknown slug
- **WHEN** a visitor requests a slug that does not exist or belongs to an unpublished catalog
- **THEN** the system returns a not-found response without revealing whether the business exists but is unpublished

#### Scenario: Product without ingredients
- **WHEN** a published catalog includes a product that has no ingredients configured
- **THEN** the public response represents that product with an empty ingredient list rather than omitting the field

#### Scenario: Product without option groups
- **WHEN** a published catalog includes a product that has no option groups configured
- **THEN** the public response represents that product with an empty option group list rather than omitting the field

#### Scenario: Product with option groups
- **WHEN** a published catalog includes a product with one or more option groups
- **THEN** the public response includes each group's name, `selection_type`, `required` flag, and its values with their name and `price_delta`

#### Scenario: Business without a cover image
- **WHEN** a published business has no cover image set
- **THEN** the public response represents it with a null cover image rather than omitting the field, and the catalog page falls back to a plain header
