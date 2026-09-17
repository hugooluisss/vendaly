# Spec Delta

## ADDED Requirements

### Requirement: Category-grouped directory listing
The public directory page SHALL render unfiltered listing results grouped into one section per category, in the order defined by the supported category list, with businesses that have no category grouped into a trailing "uncategorized" section.

#### Scenario: Unfiltered results grouped by category
- **WHEN** a visitor loads the directory with no category filter applied
- **THEN** the page renders one section per category present in the results, ordered by the supported category list, each containing only businesses with that category

#### Scenario: Uncategorized businesses grouped separately
- **WHEN** the unfiltered results include a published business with no category set
- **THEN** that business appears in a trailing "uncategorized" section, after every named-category section

#### Scenario: Category with no matching businesses
- **WHEN** a supported category has no published businesses in the current results
- **THEN** no section is rendered for that category

#### Scenario: Filtered results shown as a single section
- **WHEN** a visitor applies a category filter
- **THEN** the page renders only that category's businesses, without other category sections

### Requirement: Filter controls presented in a modal
The public directory page SHALL present the category and name filter controls inside a modal dialog opened from a filter trigger, instead of an inline form, and SHALL preserve the existing filter behavior (submitting re-runs the search with the selected category and/or name).

#### Scenario: Opening the filter modal
- **WHEN** a visitor activates the filter trigger
- **THEN** the modal opens showing the category select and name input, pre-filled with the currently applied filters

#### Scenario: Submitting filters from the modal
- **WHEN** a visitor selects a category and/or enters a name in the modal and submits
- **THEN** the directory results (and grouping) update to reflect those filters, and the modal closes

#### Scenario: Dismissing the modal without submitting
- **WHEN** a visitor closes the modal via cancel, the backdrop, or Escape without submitting
- **THEN** the modal closes and the previously applied filters remain unchanged
