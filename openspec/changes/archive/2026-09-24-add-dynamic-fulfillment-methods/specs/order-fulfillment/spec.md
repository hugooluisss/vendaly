# Spec Delta

## REMOVED Requirements

### Requirement: Fulfillment method configuration
**Reason**: Replaced by a dynamic, owner-named list of fulfillment methods (add/rename/delete, mirroring `payment-methods`) instead of a fixed set of exactly three enable/disable toggles.
**Migration**: Existing businesses' enabled fixed methods and fees are migrated into rows of the new `fulfillment_methods` table by the change's migration; no manual action needed.

#### Scenario: Enable a method
- **WHEN** the business owner enables `delivery` for their business
- **THEN** the system persists it as enabled and it becomes selectable by customers on the public catalog

#### Scenario: Enable a method with a fee
- **WHEN** the business owner enables `delivery` with a fee of 30
- **THEN** the system persists the fee, and a customer selecting `delivery` sees that fee added to their total

#### Scenario: Enable a method with no fee
- **WHEN** the business owner enables a method without setting a fee
- **THEN** the system treats it as having no fee, and it adds nothing to the customer's total

#### Scenario: Disable a method while others remain enabled
- **WHEN** the business owner disables `dine_in` while `pickup` and `delivery` are still enabled
- **THEN** the system persists the change

#### Scenario: Attempt to disable the last enabled method
- **WHEN** the business owner attempts to disable the only currently-enabled method
- **THEN** the system rejects the request with a validation error, and the method remains enabled

### Requirement: Delivery details capture
**Reason**: "Delivery" is no longer a fixed, specially-recognized method name — address/coordinate capture is now driven by a `requires_address` flag any dynamically-named method can carry.
**Migration**: The migrated "Entrega a domicilio" row (backfilled from `delivery_enabled`) carries `requires_address = true`, so existing delivery behavior is preserved without manual action.

#### Scenario: Delivery with address only
- **WHEN** a customer selects delivery and submits a free-text address with no map pin
- **THEN** the system accepts the order

#### Scenario: Delivery with pin only
- **WHEN** a customer selects delivery and submits map coordinates with no address text
- **THEN** the system accepts the order

#### Scenario: Delivery with neither
- **WHEN** a customer selects delivery and submits neither an address nor coordinates
- **THEN** the system rejects the order without creating it

### Requirement: Customer fulfillment selection validated against enabled methods
**Reason**: Replaced by id-based validation against the business's dynamic list, since fulfillment methods are no longer a fixed set of known type strings.
**Migration**: None needed — the public catalog response now returns each method's id, which the checkout flow already submits back verbatim.

#### Scenario: Select an enabled method
- **WHEN** a customer submits an order selecting `pickup`, and the business currently has `pickup` enabled
- **THEN** the system accepts the order and records the selected method

#### Scenario: Select a disabled or unknown method
- **WHEN** a customer submits an order selecting a fulfillment method the business does not currently have enabled
- **THEN** the system rejects the order without creating it

## ADDED Requirements

### Requirement: Fulfillment method configuration (dynamic list)
The system SHALL allow the business owner to add, rename, and delete named fulfillment methods for their business, each with an optional fixed fee (defaulting to none/zero) and a `requires_address` flag (defaulting to false). Every new business SHALL be seeded with one default fulfillment method named "Consumo en el local" (`requires_address = false`, no fee). The system SHALL always keep at least one fulfillment method: a request that would delete the last remaining one is rejected.

#### Scenario: Default seeded on business creation
- **WHEN** a new business is created
- **THEN** the system creates one fulfillment method for it named "Consumo en el local", with `requires_address` false and no fee

#### Scenario: Add a fulfillment method
- **WHEN** the business owner adds a fulfillment method named "Envío foráneo" with a fee of 50 and `requires_address` true
- **THEN** the system persists it and it becomes selectable by customers on the public catalog

#### Scenario: Add a fulfillment method with no fee
- **WHEN** the business owner adds a fulfillment method without setting a fee
- **THEN** the system treats it as having no fee, and it adds nothing to a customer's total when selected

#### Scenario: Delete a fulfillment method while others remain
- **WHEN** the business owner deletes a fulfillment method while at least one other remains
- **THEN** the system persists the deletion

#### Scenario: Attempt to delete the last remaining fulfillment method
- **WHEN** the business owner attempts to delete their only remaining fulfillment method
- **THEN** the system rejects the request with a validation error, and the method remains

### Requirement: Delivery details capture (by requires_address flag)
The system SHALL require, when a customer selects a fulfillment method whose `requires_address` flag is true, at least one of: a free-text delivery address, or a map-pinned latitude/longitude. Both may be provided together; neither is individually mandatory, but at least one is required. A method whose `requires_address` flag is false SHALL NOT require or prompt for either.

#### Scenario: Address-requiring method with address only
- **WHEN** a customer selects a fulfillment method with `requires_address` true and submits a free-text address with no map pin
- **THEN** the system accepts the order

#### Scenario: Address-requiring method with pin only
- **WHEN** a customer selects a fulfillment method with `requires_address` true and submits map coordinates with no address text
- **THEN** the system accepts the order

#### Scenario: Address-requiring method with neither
- **WHEN** a customer selects a fulfillment method with `requires_address` true and submits neither an address nor coordinates
- **THEN** the system rejects the order without creating it

#### Scenario: Method that does not require an address
- **WHEN** a customer selects a fulfillment method with `requires_address` false
- **THEN** the system accepts the order regardless of whether an address or coordinates were submitted

### Requirement: Customer fulfillment selection validated by id
The system SHALL require the customer to select exactly one of the business's currently-configured fulfillment methods (by id) when submitting an order, and SHALL reject the order if the selected method does not currently belong to that business.

#### Scenario: Select a configured method
- **WHEN** a customer submits an order selecting a fulfillment method that currently belongs to the business
- **THEN** the system accepts the order and records the selected method's id, name, and fee

#### Scenario: Select an unavailable or unknown method
- **WHEN** a customer submits an order selecting a fulfillment method id that does not currently belong to that business
- **THEN** the system rejects the order without creating it

#### Scenario: No fulfillment method selected
- **WHEN** a customer submits an order without selecting a fulfillment method
- **THEN** the system rejects the order without creating it

### Requirement: Fulfillment method recorded on the order
The system SHALL persist the selected fulfillment method's name and fee on the created order (snapshotted at creation time, independent of any later rename/fee change/deletion), and the generated WhatsApp message SHALL state the selected method using its snapshotted name.

#### Scenario: Fulfillment method survives a later rename
- **WHEN** the business owner renames a fulfillment method after an order that used it was created
- **THEN** that order's recorded fulfillment method name is unchanged

#### Scenario: Fulfillment method survives a later deletion
- **WHEN** the business owner deletes a fulfillment method after an order that used it was created
- **THEN** that order's recorded fulfillment method name and fee are unchanged
