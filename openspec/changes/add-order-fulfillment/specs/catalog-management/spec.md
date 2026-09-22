# Spec Delta

## MODIFIED Requirements

### Requirement: Catalog publish/unpublish
The system SHALL allow the business owner to publish or unpublish their catalog; only a published catalog is reachable through the public endpoint. Publishing SHALL require the business to have at least one fulfillment method (see `order-fulfillment`) currently enabled and at least one payment method (see `payment-methods`) configured.

#### Scenario: Publish catalog
- **WHEN** the business owner publishes their catalog and the business has at least one fulfillment method enabled and at least one payment method configured
- **THEN** the catalog becomes reachable at its public slug, showing only active products

#### Scenario: Unpublish catalog
- **WHEN** the business owner unpublishes a previously published catalog
- **THEN** subsequent public requests for that slug no longer return the catalog content

#### Scenario: Attempt to publish with no fulfillment method enabled
- **WHEN** the business owner attempts to publish a catalog while the business has zero fulfillment methods enabled
- **THEN** the system rejects the request with a validation error

#### Scenario: Attempt to publish with no payment method configured
- **WHEN** the business owner attempts to publish a catalog while the business has zero payment methods (should not normally happen, since one is seeded on creation, but deletion is still guarded independently per `payment-methods`)
- **THEN** the system rejects the request with a validation error
