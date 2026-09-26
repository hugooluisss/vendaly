# Spec Delta

## MODIFIED Requirements

### Requirement: Product/service creation and configuration
The system SHALL allow the business owner to create a product or service with a name, description, optional image, optional price, an availability flag, an optional list of named ingredients, and an optional wallet amount, assigned to one category within their business. The wallet amount is a fixed currency amount (not a percentage) credited to a customer's wallet per unit of that product sold, used only when the owning business has wallet enabled (see `customer-wallet`).

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

#### Scenario: Create product with a wallet amount
- **WHEN** the business owner submits a positive wallet amount while creating or editing a product
- **THEN** the system persists it as that product's `wallet_amount`

#### Scenario: Create product without a wallet amount
- **WHEN** the business owner submits a product with no wallet amount
- **THEN** the system creates the product with no `wallet_amount`, and it never credits a customer's wallet when ordered

#### Scenario: Reject a negative wallet amount
- **WHEN** the business owner submits a negative wallet amount for a product
- **THEN** the system rejects the update and returns a validation error identifying the field
