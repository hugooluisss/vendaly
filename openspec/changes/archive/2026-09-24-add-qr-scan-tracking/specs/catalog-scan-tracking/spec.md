# Spec Delta

## Purpose

Lets the business owner see how much their catalog QR code is actually used, by recording a scan when the public catalog is opened via the QR code and letting the owner retrieve total and per-day scan counts.

## ADDED Requirements

### Requirement: Record a catalog scan
The system SHALL expose an unauthenticated endpoint to record one scan for a business, accepting only the business identifier (resolved via its public slug), and storing no visitor-identifying data (no IP address, device fingerprint, or user identity).

#### Scenario: Valid business slug
- **WHEN** the endpoint is called with a slug matching a published business
- **THEN** the system records one scan for that business with the current timestamp and returns success

#### Scenario: Unknown or unpublished business slug
- **WHEN** the endpoint is called with a slug that does not match any published business
- **THEN** the system does not record a scan and returns a not-found response

### Requirement: Retrieve scan counts
The system SHALL expose an authenticated endpoint, restricted to the business owner, that returns the total number of recorded scans and a per-day count series for their business.

#### Scenario: Owner requests scan stats
- **WHEN** the authenticated business owner requests scan stats for their own business
- **THEN** the system returns the total scan count and a list of per-day counts

#### Scenario: No scans recorded yet
- **WHEN** the authenticated business owner requests scan stats and no scans have been recorded
- **THEN** the system returns a total of zero and an empty per-day series

#### Scenario: Requesting another business's stats
- **WHEN** an authenticated user requests scan stats for a business they do not own
- **THEN** the system denies the request

### Requirement: No deduplication of repeated scans
The system SHALL count every qualifying page load as a separate scan, including repeated loads from the same visitor in the same session; it SHALL NOT attempt to deduplicate, rate-limit, or identify unique visitors.

#### Scenario: Same visitor reloads the catalog page via QR
- **WHEN** a visitor reloads the public catalog page with the QR scan marker present multiple times
- **THEN** each qualifying load is recorded as a separate scan
