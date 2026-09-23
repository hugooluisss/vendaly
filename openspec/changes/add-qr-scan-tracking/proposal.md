# Proposal

## Why

The dashboard's redesigned "Scans" section (`redesign-dashboard-home-ui`) currently has nowhere to pull real numbers from — the business owner has no way to know how many people actually scan their catalog QR code. Adding a minimal scan counter (total + per day) lets the owner see whether their QR/catalog is getting used, without building out full visitor analytics.

## What Changes

- The QR code encoding a business's public catalog URL includes a query marker (e.g. `?src=qr`) so a page load can be attributed to a QR scan rather than a direct link visit.
- The public catalog page, when loaded with that marker present, calls a new unauthenticated endpoint that records one scan for the business (business id + timestamp only — no visitor identity, IP, or device data is stored).
- A new authenticated endpoint lets the business owner fetch their business's total scan count and a per-day count series for a date range, which the dashboard uses instead of the placeholder zero/empty data from `redesign-dashboard-home-ui`.
- **Accepted limitation**: a reload of the catalog page with the marker still present records another scan — there is no de-duplication, rate-limiting, or unique-visitor logic in this version. This is a deliberate scope cut, not an oversight.
- Out of scope: unique-visitor counting, operating system/device/browser breakdown, any PII collection.

## Capabilities

### New Capabilities
- `catalog-scan-tracking`: Recording a catalog-page view as a "scan" when it originates from the QR code, and letting the business owner retrieve scan totals/per-day counts for their business.

### Modified Capabilities
- `public-catalog`: The QR-code generation requirement changes — the encoded URL must include the scan-source marker so scans can be attributed.

## Impact

- **Backend**: new `catalog_scans` table/migration, `CatalogScan` entity, repository interface + Cycle implementation, a service method to record and query scans, a new public POST endpoint (record) and a new authenticated GET endpoint (query) under `src/Web`.
- **Frontend**: QR generation (the shared `QrCodeComponent` from `redesign-dashboard-home-ui`, or today's inline logic in `business-settings.component.ts` if that change hasn't landed yet) appends the query marker to the encoded URL; `catalog-page.component.ts` fires the record call when the marker is present; `ScanStatsService` (placeholder from `redesign-dashboard-home-ui`) is updated to call the new authenticated endpoint instead of returning a hardcoded empty result.
- **Dependency direction**: this change is additive to `redesign-dashboard-home-ui` — it does not require that change to land first (the record/query endpoints and the QR marker are independently useful), but it does replace that change's placeholder `ScanStatsService` implementation once both are applied.
