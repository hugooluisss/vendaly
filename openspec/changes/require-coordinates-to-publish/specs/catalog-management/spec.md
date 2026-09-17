## MODIFIED Requirements

### Requirement: Catalog publish/unpublish
The system SHALL allow the business owner to publish or unpublish their catalog; only a published catalog is reachable through the public endpoint. Publishing SHALL require the business to already have coordinates (latitude/longitude) set.

#### Scenario: Publish catalog
- **WHEN** the business owner publishes their catalog and the business already has coordinates set
- **THEN** the catalog becomes reachable at its public slug, showing only active products

#### Scenario: Unpublish catalog
- **WHEN** the business owner unpublishes a previously published catalog
- **THEN** subsequent public requests for that slug no longer return the catalog content

#### Scenario: Reject publishing without coordinates
- **WHEN** the business owner attempts to publish a catalog for a business that has no coordinates set
- **THEN** the system rejects the request with an error indicating that a location must be set first, and the catalog remains unpublished

#### Scenario: Unpublishing never requires coordinates
- **WHEN** the business owner unpublishes their catalog
- **THEN** the system allows it regardless of whether the business has coordinates set

#### Scenario: Already-published business unaffected
- **WHEN** a business that is already published has no coordinates set (e.g. it was published before this requirement existed)
- **THEN** it remains published and reachable; this requirement only gates the act of publishing, not existing published state
