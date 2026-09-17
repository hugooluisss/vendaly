## MODIFIED Requirements

### Requirement: Public business directory listing
The system SHALL expose a public, unauthenticated endpoint listing published businesses, each with its name, logo, category, location, coordinates (or null if unset), and slug (for linking to its catalog).

#### Scenario: List published businesses
- **WHEN** a visitor requests the directory with no filters
- **THEN** the system returns every published business, unpublished businesses excluded

#### Scenario: Business without category or location still listed
- **WHEN** a published business has no category and/or no location set
- **THEN** it still appears in the unfiltered directory listing

#### Scenario: Business without coordinates still listed
- **WHEN** a published business has no coordinates set
- **THEN** it still appears in directory listings and category/text-location searches, with a null coordinate pair, and is excluded only from a distance-sorted result

## ADDED Requirements

### Requirement: Distance-based sorting
The system SHALL allow sorting the directory by distance from a visitor-supplied position (latitude/longitude), computed server-side using the haversine formula against each business's stored coordinates, without calling any external distance or routing API.

#### Scenario: Sort by proximity
- **WHEN** a visitor requests the directory with their position
- **THEN** the system returns published businesses that have coordinates, ordered nearest-first, excluding businesses without coordinates

#### Scenario: No position supplied
- **WHEN** a visitor requests the directory without a position
- **THEN** the system returns results using the existing category/text-location filtering and ordering, unaffected by this requirement

#### Scenario: Combine proximity with category filter
- **WHEN** a visitor requests the directory with both a position and a category filter
- **THEN** the system returns only businesses matching that category, ordered nearest-first

### Requirement: Map display of directory results
The system SHALL make each listed business's coordinates available to the public directory page so it can render them as pins on a map, using a tile provider that requires no paid API key.

#### Scenario: Businesses with coordinates appear as pins
- **WHEN** the directory page loads results that include businesses with coordinates
- **THEN** each such business is representable as a pin at its exact stored coordinates

#### Scenario: Businesses without coordinates are not plotted
- **WHEN** a listed business has no coordinates
- **THEN** it is excluded from the map pins while still appearing in the list view
