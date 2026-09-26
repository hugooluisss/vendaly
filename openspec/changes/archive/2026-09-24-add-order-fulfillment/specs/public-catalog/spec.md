# Spec Delta

## MODIFIED Requirements

### Requirement: Public catalog lookup by slug
The system SHALL expose `GET /public/catalog/:slug` without requiring authentication, returning the business's profile (including its cover image, when set), opening hours, enabled fulfillment methods (each with its fee, when set), payment methods, categories, and active products (each with its ingredient list, when any exist) when the catalog is published.

#### Scenario: Published catalog
- **WHEN** a visitor requests a slug belonging to a published catalog
- **THEN** the system returns the business info (including its cover image if set), opening hours, enabled fulfillment methods (with fees), payment methods, categories, and only active products, each including its ingredients if it has any

#### Scenario: Unpublished or unknown slug
- **WHEN** a visitor requests a slug that does not exist or belongs to an unpublished catalog
- **THEN** the system returns a not-found response without revealing whether the business exists but is unpublished

#### Scenario: Product without ingredients
- **WHEN** a published catalog includes a product that has no ingredients configured
- **THEN** the public response represents that product with an empty ingredient list rather than omitting the field

#### Scenario: Business without a cover image
- **WHEN** a published business has no cover image set
- **THEN** the public response represents it with a null cover image rather than omitting the field, and the catalog page falls back to a plain header

#### Scenario: Enabled fulfillment methods included
- **WHEN** a visitor requests a slug belonging to a published catalog
- **THEN** the response lists exactly the fulfillment methods currently enabled for that business, each with its fee if one is set (a published catalog always has at least one, per the publish precondition)

#### Scenario: Payment methods included
- **WHEN** a visitor requests a slug belonging to a published catalog
- **THEN** the response lists exactly the business's currently-configured payment methods (a published catalog always has at least one, per the publish precondition)
