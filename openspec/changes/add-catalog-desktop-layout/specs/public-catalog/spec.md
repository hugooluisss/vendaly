# Spec Delta

## ADDED Requirements

### Requirement: Desktop layout for the public catalog page
The public catalog page SHALL present a multi-column desktop layout at wide viewports (starting at the same breakpoint the site already uses for its widest layout adjustments), with the business header, category navigation, and hours grouped in a persistent left column and the product listing rendered as a multi-column grid in a right column, instead of a single stacked column merely rendered wider.

#### Scenario: Wide viewport shows two-column arrangement
- **WHEN** a visitor loads the catalog page at a desktop-width viewport
- **THEN** the business header, category navigation, and hours render together in a left column, and the product listing renders as a multi-column grid in a right column

#### Scenario: Category navigation stays reachable while browsing products
- **WHEN** a visitor at a desktop-width viewport scrolls through the product listing
- **THEN** the category navigation in the left column remains visible without requiring the visitor to scroll back to the top of the page

#### Scenario: Narrow viewport keeps the existing single-column layout
- **WHEN** a visitor loads the catalog page at a mobile-width viewport
- **THEN** the page renders the existing single stacked column (header, horizontally-scrollable category chips, hours, then products) unchanged

#### Scenario: Product options modal and cart bar remain functional in the desktop layout
- **WHEN** a visitor adds a product with configurable options to their selection at a desktop-width viewport
- **THEN** the options modal and the floating cart summary behave the same as in the single-column layout, unaffected by the two-column arrangement
