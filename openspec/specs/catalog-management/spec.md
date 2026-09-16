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
The system SHALL allow the business owner to publish or unpublish their catalog; only a published catalog is reachable through the public endpoint.

#### Scenario: Publish catalog
- **WHEN** the business owner publishes their catalog
- **THEN** the catalog becomes reachable at its public slug, showing only active products

#### Scenario: Unpublish catalog
- **WHEN** the business owner unpublishes a previously published catalog
- **THEN** subsequent public requests for that slug no longer return the catalog content

### Requirement: Product model extensibility for future options
The system SHALL model `Product` so that `ProductOption`/`ProductOptionValue` (variants, extras) can be introduced later without a breaking schema change, even though they are not implemented in this version.

#### Scenario: Product created without options
- **WHEN** a product is created in this version
- **THEN** it has no options/variants, and its schema does not preclude attaching option groups in a future version
