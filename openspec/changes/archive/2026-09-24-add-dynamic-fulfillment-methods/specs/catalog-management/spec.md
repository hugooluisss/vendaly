# Spec Delta

## MODIFIED Requirements

### Requirement: Catalog publish/unpublish
The system SHALL allow the business owner to publish or unpublish their catalog; only a published catalog is reachable through the public endpoint. Publishing SHALL require, in this order: the business already has coordinates (latitude/longitude) set, at least one fulfillment method (see `order-fulfillment`) configured, and at least one payment method (see `payment-methods`) configured.

#### Scenario: Publish catalog
- **WHEN** the business owner publishes their catalog and the business already has coordinates set, at least one fulfillment method configured, and at least one payment method configured
- **THEN** the catalog becomes reachable at its public slug, showing only active products

#### Scenario: Unpublish catalog
- **WHEN** the business owner unpublishes a previously published catalog
- **THEN** subsequent public requests for that slug no longer return the catalog content

#### Scenario: Reject publishing without coordinates
- **WHEN** the business owner attempts to publish a catalog for a business that has no coordinates set
- **THEN** the system rejects the request with an error indicating that a location must be set first, and the catalog remains unpublished

#### Scenario: Attempt to publish with no fulfillment method enabled
- **WHEN** the business owner attempts to publish a catalog while the business has zero fulfillment methods configured
- **THEN** the system rejects the request with a validation error

#### Scenario: Attempt to publish with no payment method configured
- **WHEN** the business owner attempts to publish a catalog while the business has zero payment methods configured
- **THEN** the system rejects the request with a validation error

#### Scenario: Unpublishing never requires coordinates or fulfillment/payment methods
- **WHEN** the business owner unpublishes their catalog
- **THEN** the system allows it regardless of whether the business has coordinates, a fulfillment method, or a payment method

#### Scenario: Already-published business unaffected by later requirement additions
- **WHEN** a business that is already published is missing coordinates, a fulfillment method, or a payment method (e.g. it was published before one of these requirements existed)
- **THEN** it remains published and reachable; these requirements only gate the act of publishing, not existing published state
