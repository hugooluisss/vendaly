# Spec Delta

## Purpose

Lets a business owner attach configurable choice groups (interchangeable single-select options, or optional multi-select add-ons) to a product, each choice optionally carrying a price adjustment.

## ADDED Requirements

### Requirement: Option group configuration
The system SHALL allow the business owner to create, update, reorder, and delete `ProductOption` groups on their own products. Each group has a name, a `selection_type` of `single` (customer picks exactly one value) or `multiple` (customer picks zero or more values), and a `required` flag. A `required` flag of `true` is only meaningful for `single` groups; a `multiple` group is always optional at the group level (individual values within it are independently optional).

#### Scenario: Create single-select required group
- **WHEN** the business owner creates an option group named "Sugar type" with `selection_type` `single` and `required` true, containing values "Brown sugar", "Refined sugar", "Splenda"
- **THEN** the system creates the `ProductOption` with those three `ProductOptionValue`s, and the customer must select exactly one when ordering

#### Scenario: Create multi-select optional group
- **WHEN** the business owner creates an option group named "Add-ons" with `selection_type` `multiple`, containing a value "Whipped cream" with a price delta
- **THEN** the system creates the `ProductOption` and its value, and the customer may select zero, one, or more values from it when ordering

#### Scenario: Delete option group
- **WHEN** the business owner deletes an option group from a product
- **THEN** the system removes the group and its values; existing orders that already recorded a selection from that group are unaffected

#### Scenario: Reorder option groups and values
- **WHEN** the business owner changes the display order of a product's option groups or of the values within a group
- **THEN** the system persists the new order and the public catalog reflects it

### Requirement: Option value pricing
The system SHALL allow each `ProductOptionValue` to carry an optional `price_delta` (signed, positive or negative), defaulting to 0, that is added to the item's unit price when the customer selects that value.

#### Scenario: Value with a positive price delta
- **WHEN** the business owner sets a `price_delta` of 15 on the "Whipped cream" value
- **THEN** selecting that value on an order item adds 15 to that item's unit price

#### Scenario: Value with no price delta
- **WHEN** the business owner creates a value without specifying a `price_delta`
- **THEN** the system stores it as 0 and selecting that value does not change the item's price

### Requirement: Options require a priced product
The system SHALL require a product to have a price before option groups with non-zero price deltas can be attached to it, since a priceless (quotation-style) product has no unit price for a delta to adjust.

#### Scenario: Attempt to add a priced option to a priceless product
- **WHEN** the business owner tries to add an option value with a non-zero `price_delta` to a product that has no price
- **THEN** the system rejects the request with a validation error

#### Scenario: Zero-delta options on a priceless product
- **WHEN** the business owner adds an option group to a priceless product, with all its values at a `price_delta` of 0
- **THEN** the system allows it, since no price calculation is affected
