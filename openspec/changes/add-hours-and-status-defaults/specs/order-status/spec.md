# Spec Delta

## MODIFIED Requirements

### Requirement: Order status list management
The system SHALL allow the business owner to add, rename, recolor, reorder, and delete named order statuses for their business. Each status has a name, a color, an `is_terminal` flag, and an `is_default` flag. Every new business SHALL be seeded with six statuses, in order: "recibido" (`is_default` true), "confirmado", "preparando", "entregado", "terminado" (`is_terminal` true), "cancelado" (`is_terminal` true).

#### Scenario: Default seeded on business creation
- **WHEN** a new business is created
- **THEN** the system creates six statuses for it — "recibido", "confirmado", "preparando", "entregado", "terminado", "cancelado" — in that order, with "recibido" marked as the default and "terminado"/"cancelado" marked as terminal

#### Scenario: Add a status
- **WHEN** the business owner adds a status named "En camino" with a color
- **THEN** the system persists it, and it becomes assignable to the business's orders

#### Scenario: Rename or recolor a status
- **WHEN** the business owner renames a status or changes its color
- **THEN** the system persists the change, and any order already assigned that status keeps its assignment (the status's identity, not a copy of its old name/color, is what's referenced)

#### Scenario: Reorder statuses
- **WHEN** the business owner changes the display order of their statuses
- **THEN** the system persists the new order

#### Scenario: Existing business with unmodified default statuses
- **WHEN** an existing business's statuses still exactly match the four previous default names ("Creado", "Elaborando", "Entregado", "Cancelado") at the time this change is applied
- **THEN** the system renames them in place, preserving each status's id and any order assignments, to "recibido", "preparando", "entregado", and "cancelado" respectively, then adds "confirmado" and "terminado" if the business does not already have statuses with those names

#### Scenario: Existing business with customized statuses
- **WHEN** an existing business's statuses have been renamed, deleted, or added to by the owner such that they no longer exactly match the four previous default names
- **THEN** the system does not rename or remove any of that business's existing statuses, and only adds "confirmado" and "terminado" if the business does not already have statuses with those names
