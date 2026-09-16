## Purpose

Gives customers unauthenticated, mobile-first access to a business's published catalog by slug, and gives the business a shareable QR code pointing at that same URL.

## ADDED Requirements

### Requirement: Public catalog lookup by slug
The system SHALL expose `GET /public/catalog/:slug` without requiring authentication, returning the business's profile, opening hours, categories, and active products when the catalog is published.

#### Scenario: Published catalog
- **WHEN** a visitor requests a slug belonging to a published catalog
- **THEN** the system returns the business info, opening hours, categories, and only active products

#### Scenario: Unpublished or unknown slug
- **WHEN** a visitor requests a slug that does not exist or belongs to an unpublished catalog
- **THEN** the system returns a not-found response without revealing whether the business exists but is unpublished

### Requirement: Inactive products excluded from public view
The system SHALL never include a product marked inactive in the response of the public catalog endpoint, even if it belongs to a published catalog.

#### Scenario: Mixed active and inactive products
- **WHEN** a published catalog has both active and inactive products
- **THEN** the public response includes only the active ones

### Requirement: QR code for public catalog URL
The system SHALL generate a QR code encoding the business's public catalog URL, retrievable by the authenticated business owner.

#### Scenario: Generate QR code
- **WHEN** the business owner requests the QR code for their published catalog
- **THEN** the system returns an image encoding the exact public catalog URL for their slug
