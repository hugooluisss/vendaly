# Spec Delta

## MODIFIED Requirements

### Requirement: Order status list management
The system SHALL allow the business owner to add, rename, recolor, reorder, and delete named order statuses for their business. Each status has a name, a color, an `is_terminal` flag, an `is_default` flag, and a `reverses_wallet` flag. Every new business SHALL be seeded with four statuses: "Creado" (`is_default` true), "Elaborando", "Entregado" (`is_terminal` true), "Cancelado" (`is_terminal` true, `reverses_wallet` true).

#### Scenario: Default seeded on business creation
- **WHEN** a new business is created
- **THEN** the system creates four statuses for it — "Creado", "Elaborando", "Entregado", "Cancelado" — with "Creado" marked as the default, "Entregado"/"Cancelado" marked as terminal, and "Cancelado" additionally marked `reverses_wallet`

#### Scenario: Add a status
- **WHEN** the business owner adds a status named "En camino" with a color
- **THEN** the system persists it, and it becomes assignable to the business's orders

#### Scenario: Rename or recolor a status
- **WHEN** the business owner renames a status or changes its color
- **THEN** the system persists the change, and any order already assigned that status keeps its assignment (the status's identity, not a copy of its old name/color, is what's referenced)

#### Scenario: Reorder statuses
- **WHEN** the business owner changes the display order of their statuses
- **THEN** the system persists the new order

#### Scenario: Mark a status as reversing the wallet
- **WHEN** the business owner marks a status (e.g. a custom "Rechazado" status) with `reverses_wallet` true
- **THEN** the system persists the flag, and an order transitioning into that status later triggers a wallet reversal (see `customer-wallet`)

## ADDED Requirements

### Requirement: Wallet reversal flag is independent of terminal/default
The `reverses_wallet` flag SHALL be settable independently of `is_terminal` and `is_default`: a business MAY mark a non-terminal status as reversing the wallet, and MAY have zero or more than one status marked `reverses_wallet`.

#### Scenario: Non-terminal status marked as reversing
- **WHEN** the business owner marks a non-terminal status as `reverses_wallet`
- **THEN** the system accepts it, and an order moved to that status still triggers a wallet reversal

#### Scenario: No status marked as reversing
- **WHEN** a business has no status with `reverses_wallet` set
- **THEN** no order transition for that business ever triggers a wallet reversal
