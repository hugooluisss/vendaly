## Purpose

Lets a customer discover published businesses on Vendaly by browsing or searching by category and location, without needing a direct link or QR code first.

## Requirements

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

#### Scenario: Map pins are not limited by incremental list loading
- **WHEN** the rendered list currently shows fewer businesses than the full filtered set because of incremental loading
- **THEN** the map still shows a pin for every matching business that has coordinates, not only the ones currently rendered in the list

### Requirement: Floating category filter chips
The public directory page SHALL present each supported category as a toggleable floating chip button, always visible (no modal), allowing zero or more categories to be active at once. The name search input SHALL also remain always visible, combinable with any active category chips.

#### Scenario: No chip active shows every category
- **WHEN** a visitor has no category chip active
- **THEN** the directory shows businesses from every category, as one unified list

#### Scenario: One or more chips active narrows results
- **WHEN** a visitor activates one or more category chips
- **THEN** the directory shows only businesses belonging to an active category

#### Scenario: Toggling a chip updates results immediately
- **WHEN** a visitor activates or deactivates a category chip
- **THEN** the displayed results update immediately, without a page navigation

#### Scenario: Name search combines with active chips
- **WHEN** a visitor has one or more category chips active and enters a name search term
- **THEN** the directory shows only businesses matching both the active categories and the name term

### Requirement: Incremental loading of directory results
The public directory page SHALL initially render only the first 10 businesses of the current filtered set, as a single unified list, and SHALL automatically load and append the next 10 businesses as the visitor scrolls to the end of the currently rendered list, continuing until every matching business has been rendered.

#### Scenario: Initial load caps at the batch size
- **WHEN** the current filtered set has more than 10 matching businesses
- **THEN** only the first 10 are rendered initially

#### Scenario: Reaching the end loads more
- **WHEN** a visitor scrolls to the end of the currently rendered list and more matching businesses remain
- **THEN** the next 10 matching businesses are loaded and appended automatically, without a manual "load more" click or page navigation

#### Scenario: Small result sets need no further loading
- **WHEN** the current filtered set has 10 or fewer matching businesses
- **THEN** all of them are rendered immediately and no further loading occurs

#### Scenario: Changing filters resets pagination
- **WHEN** a visitor changes the active category chips or the name search term
- **THEN** the rendered list resets to showing the first 10 businesses of the new filtered set
