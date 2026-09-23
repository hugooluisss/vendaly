# Spec Delta

## Purpose

Gives the business owner a single landing screen that summarizes their catalog's setup status, exposes its public QR code, and surfaces catalog scan activity, so they don't have to visit separate screens to check on their business's public presence.

## ADDED Requirements

### Requirement: Business status header
The system SHALL show, on the owner's dashboard landing screen, the business name and whether the business is currently published or unpublished.

#### Scenario: Business configured
- **WHEN** the owner has already created their business
- **THEN** the header displays the business name and a published/unpublished indicator

#### Scenario: No business yet
- **WHEN** the owner has not created a business yet
- **THEN** the screen shows a prompt to create or configure the business instead of the header/QR/stats sections

### Requirement: Catalog QR code visibility
The system SHALL display the business's public catalog QR code on the dashboard landing screen when the business has a public catalog URL, with an action to download it.

#### Scenario: Business has a public catalog URL
- **WHEN** the owner's business has a slug and public catalog URL
- **THEN** the landing screen renders a scannable QR code pointing to that URL and offers a download action

#### Scenario: Business has no public catalog URL yet
- **WHEN** the owner's business has no slug/public URL yet
- **THEN** the landing screen does not attempt to render a QR code and instead indicates the catalog isn't published yet

### Requirement: Catalog composition stats
The system SHALL show, on the dashboard landing screen, counts of the business's categories, total products, and active vs. paused products.

#### Scenario: Business has catalog data
- **WHEN** the owner's business has categories and products
- **THEN** the landing screen shows the category count, total product count, and active/paused product counts

### Requirement: Catalog scan activity view
The system SHALL show, on the dashboard landing screen, the total number of recorded catalog scans and a per-day breakdown, defaulting to an empty/zero state when no scan data is available.

#### Scenario: No scan data available
- **WHEN** no scan activity has been recorded for the business (including when scan tracking is not yet available)
- **THEN** the landing screen shows a zero total and an empty-state message in place of the per-day chart, without erroring

#### Scenario: Scan data available
- **WHEN** scan activity has been recorded for the business
- **THEN** the landing screen shows the total scan count and a per-day chart of scan counts
