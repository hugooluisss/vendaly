## Purpose

Lets a business owner build and maintain the categories and products/services that make up their catalog, and control when that catalog becomes publicly visible.

## Requirements

### Requirement: Category management
The system SHALL allow the business owner to create, update, reorder, and delete categories within their own business's catalog.

When a category still contains products, deletion is blocked with a validation error. Products are never silently deleted or reassigned.

#### Scenario: Create category
- **WHEN** the business owner submits a category name
- **THEN** the system creates a `Category` scoped to that business

#### Scenario: Delete category with products
- **WHEN** the business owner deletes a category that still has products assigned to it
- **THEN** the system either blocks the deletion or reassigns/orphans the products in a defined, non-silent way (implementation decides which; behavior must be explicit and documented, not accidental data loss)

### Requirement: Product/service creation and configuration
The system SHALL allow the business owner to create a product or service with a name, description, optional image, optional price, an availability flag, and an optional list of named ingredients, assigned to one category within their business.

#### Scenario: Create product with price
- **WHEN** the business owner submits a name and a numeric price for a new product
- **THEN** the system creates the `Product` with that price and defaults it to active/available

#### Scenario: Create product without price
- **WHEN** the business owner submits a name but omits the price
- **THEN** the system creates the `Product` with no price, supporting quotation-style listings

#### Scenario: Toggle availability
- **WHEN** the business owner marks a product as inactive
- **THEN** the product is excluded from the public catalog while remaining editable in the management view

#### Scenario: Create product with ingredients
- **WHEN** the business owner submits one or more named ingredients while creating or editing a product
- **THEN** the system stores each ingredient associated with that product, in the submitted order

#### Scenario: Create product without ingredients
- **WHEN** the business owner submits a product with no ingredients
- **THEN** the system creates the product with an empty ingredient list, which is valid and does not block creation

#### Scenario: Edit a product's ingredient list
- **WHEN** the business owner adds, removes, or reorders ingredients on an existing product
- **THEN** the system replaces the stored ingredient list to match exactly what was submitted, without affecting the product's other fields

### Requirement: Product image
The system SHALL allow at most one image to be attached to a product, stored in object storage, and replaced or removed by the business owner.

#### Scenario: Attach image
- **WHEN** the business owner uploads an image for a product
- **THEN** the system stores it and associates it with that `Product` via `ProductImage`

#### Scenario: Replace image
- **WHEN** the business owner uploads a new image for a product that already has one
- **THEN** the system replaces the existing association without leaving the product imageless mid-update

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

### Requirement: Product option groups
The system SHALL allow the business owner to attach `ProductOption` groups (see `product-options` capability) to a product they own, in addition to the product's name, description, optional image, optional price, availability flag, and ingredient list.

#### Scenario: Product created without options
- **WHEN** a product is created without any option groups
- **THEN** it has no options/variants, which is valid and does not block creation

#### Scenario: Product created with options
- **WHEN** the business owner attaches one or more option groups to a product they own
- **THEN** the system associates those groups with the product, and they appear in the product's management view and public representation
