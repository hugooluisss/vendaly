## Purpose

Gives customers unauthenticated, mobile-first access to a business's published catalog by slug, and gives the business a shareable QR code pointing at that same URL.

## Requirements

### Requirement: Public catalog lookup by slug
The system SHALL expose `GET /public/catalog/:slug` without requiring authentication, returning the business's profile (including its cover image, when set), opening hours, configured fulfillment methods (each with its name, fee when set, and `requires_address` flag), payment methods, categories, and active products (each with its ingredient list and option groups/values, when any exist) when the catalog is published.

#### Scenario: Published catalog
- **WHEN** a visitor requests a slug belonging to a published catalog
- **THEN** the system returns the business info (including its cover image if set), opening hours, configured fulfillment methods (with names, fees, and `requires_address` flags), payment methods, categories, and only active products, each including its ingredients and option groups/values if it has any

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

#### Scenario: Enabled fulfillment methods included
- **WHEN** a visitor requests a slug belonging to a published catalog
- **THEN** the response lists exactly the business's currently-configured fulfillment methods, each with its id, name, fee if one is set, and `requires_address` flag (a published catalog always has at least one, per the publish precondition)

#### Scenario: Payment methods included
- **WHEN** a visitor requests a slug belonging to a published catalog
- **THEN** the response lists exactly the business's currently-configured payment methods (a published catalog always has at least one, per the publish precondition)

### Requirement: Inactive products excluded from public view
The system SHALL never include a product marked inactive in the response of the public catalog endpoint, even if it belongs to a published catalog.

#### Scenario: Mixed active and inactive products
- **WHEN** a published catalog has both active and inactive products
- **THEN** the public response includes only the active ones

### Requirement: QR code for public catalog URL
The system SHALL let the business owner view and download a QR code encoding the exact public catalog URL for their business, appended with a scan-source marker query parameter, generated client-side from the business's slug — without a dedicated backend QR-image endpoint.

#### Scenario: View QR code for a published, slugged business
- **WHEN** the business owner has a business with a slug
- **THEN** the business settings page renders a QR code encoding the public catalog URL for that slug, with the scan-source marker included

#### Scenario: Download the QR code
- **WHEN** the business owner requests to download the displayed QR code
- **THEN** the system provides it as a PNG image file encoding the same public catalog URL (with scan-source marker) shown on screen

#### Scenario: No business or no slug yet
- **WHEN** the business owner has no business, or their business has no slug
- **THEN** the business settings page does not render a QR code
