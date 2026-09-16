## MODIFIED Requirements

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
