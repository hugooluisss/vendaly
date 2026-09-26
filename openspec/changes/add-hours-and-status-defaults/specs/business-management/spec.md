# Spec Delta

## MODIFIED Requirements

### Requirement: Opening hours configuration
The system SHALL allow the business owner to configure opening hours per day of the week, including marking a day as closed. Every new business SHALL be seeded with default hours: Monday through Friday open 09:00–20:00, Saturday and Sunday closed. Any existing business with no opening hours configured SHALL be backfilled with the same default.

#### Scenario: Set weekly hours
- **WHEN** the business owner submits an open and close time for one or more days of the week
- **THEN** the system persists those hours for each specified day

#### Scenario: Mark a day closed
- **WHEN** the business owner marks a day of the week as closed
- **THEN** the system persists that day as closed regardless of any previously set hours

#### Scenario: Default seeded on business creation
- **WHEN** a new business is created
- **THEN** the system creates opening hours for it: Monday through Friday open 09:00–20:00, Saturday and Sunday closed

#### Scenario: Existing business with no configured hours
- **WHEN** an existing business has zero opening-hours rows at the time this change is applied
- **THEN** the system backfills the same default (Monday–Friday 09:00–20:00, Saturday/Sunday closed) for it

#### Scenario: Existing business with hours already configured is left alone
- **WHEN** an existing business already has at least one opening-hours row
- **THEN** the system does not modify its configured hours
