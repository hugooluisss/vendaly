## Purpose

Lets a customer discover published businesses on Vendaly by browsing or searching by category and location, without needing a direct link or QR code first.

## ADDED Requirements

### Requirement: Public business directory listing
The system SHALL expose a public, unauthenticated endpoint listing published businesses, each with its name, logo, category, location, and slug (for linking to its catalog).

#### Scenario: List published businesses
- **WHEN** a visitor requests the directory with no filters
- **THEN** the system returns every published business, unpublished businesses excluded

#### Scenario: Business without category or location still listed
- **WHEN** a published business has no category and/or no location set
- **THEN** it still appears in the unfiltered directory listing

### Requirement: Filter by category
The system SHALL allow filtering the directory to businesses matching an exact category value from the supported fixed list.

#### Scenario: Filter to one category
- **WHEN** a visitor requests the directory filtered to a specific category
- **THEN** the system returns only published businesses with that exact category, excluding businesses with no category set

### Requirement: Filter by location
The system SHALL allow filtering the directory to businesses whose location text matches (partial, case-insensitive) the requested location term.

#### Scenario: Filter by a location term
- **WHEN** a visitor requests the directory filtered by a location term
- **THEN** the system returns only published businesses whose location contains that term, excluding businesses with no location set

#### Scenario: Combined category and location filters
- **WHEN** a visitor requests the directory with both a category and a location term
- **THEN** the system returns only published businesses matching both filters
