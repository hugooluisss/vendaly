# Spec Delta

## Purpose

Lets a business define the stages an order moves through (named, colored, one default, some terminal), and lets the owner track each order against that list.

## ADDED Requirements

### Requirement: Order status list management
The system SHALL allow the business owner to add, rename, recolor, reorder, and delete named order statuses for their business. Each status has a name, a color, an `is_terminal` flag, and an `is_default` flag. Every new business SHALL be seeded with four statuses: "Creado" (`is_default` true), "Elaborando", "Entregado" (`is_terminal` true), "Cancelado" (`is_terminal` true).

#### Scenario: Default seeded on business creation
- **WHEN** a new business is created
- **THEN** the system creates four statuses for it — "Creado", "Elaborando", "Entregado", "Cancelado" — with "Creado" marked as the default and "Entregado"/"Cancelado" marked as terminal

#### Scenario: Add a status
- **WHEN** the business owner adds a status named "En camino" with a color
- **THEN** the system persists it, and it becomes assignable to the business's orders

#### Scenario: Rename or recolor a status
- **WHEN** the business owner renames a status or changes its color
- **THEN** the system persists the change, and any order already assigned that status keeps its assignment (the status's identity, not a copy of its old name/color, is what's referenced)

#### Scenario: Reorder statuses
- **WHEN** the business owner changes the display order of their statuses
- **THEN** the system persists the new order

### Requirement: Exactly one default status
The system SHALL ensure a business always has exactly one status marked `is_default`. Setting a new status as default SHALL automatically unset the previous default. The system SHALL reject deleting the current default status.

#### Scenario: Set a new default
- **WHEN** the business owner marks a different status as default
- **THEN** the system persists it as the new default and the previously-default status is no longer marked default

#### Scenario: Attempt to delete the default status
- **WHEN** the business owner attempts to delete the status currently marked as default
- **THEN** the system rejects the request with a validation error, instructing that a different default be set first

### Requirement: Status deletion guards
The system SHALL always keep at least one status: a request that would delete the last remaining status is rejected. The system SHALL reject deleting a status that is currently assigned to one or more orders.

#### Scenario: Attempt to delete the last remaining status
- **WHEN** the business owner attempts to delete their only remaining status
- **THEN** the system rejects the request with a validation error

#### Scenario: Attempt to delete a status in use
- **WHEN** the business owner attempts to delete a status that is currently assigned to at least one order
- **THEN** the system rejects the request with a validation error

#### Scenario: Delete an unused, non-default status
- **WHEN** the business owner deletes a status that is not the default and has no orders currently assigned to it
- **THEN** the system persists the deletion

### Requirement: Default status assigned on order creation
The system SHALL assign a newly-created order the business's current default status.

#### Scenario: New order gets the default status
- **WHEN** a customer submits an order for a business whose default status is "Creado"
- **THEN** the created order is assigned "Creado"

### Requirement: Business owner changes an order's status
The system SHALL allow the business owner to change an order's status to any status currently defined for that business.

#### Scenario: Change status
- **WHEN** the business owner updates an order's status to one of their business's currently-defined statuses
- **THEN** the system persists the change and it's reflected in the order-history view

#### Scenario: Attempt to assign a status from another business
- **WHEN** the business owner attempts to set an order's status to a status id that doesn't belong to their own business
- **THEN** the system rejects the request with a validation error
