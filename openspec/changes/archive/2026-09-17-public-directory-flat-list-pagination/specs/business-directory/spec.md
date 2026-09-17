# Spec Delta

## REMOVED Requirements

### Requirement: Category-grouped directory listing
**Reason**: Splitting results into one section per category read as many separate lists; replaced by a single unified list, still narrowable by the (unchanged) category filter chips.
**Migration**: Frontend-only UI change; no data or API migration needed.

## MODIFIED Requirements

### Requirement: Incremental loading of directory results
The public directory page SHALL initially render only the first 10 businesses of the current filtered set, as a single unified list, and SHALL automatically load and append the next 10 businesses as the visitor scrolls to the end of the currently rendered list, continuing until every matching business has been rendered.

#### Scenario: Initial load caps at 18
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
