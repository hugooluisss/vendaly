# Design

## Context

`dashboard-home.component.ts` currently fetches the business, its categories, and its products, and renders four stat blocks plus two text links (see proposal.md - Why). Separately, `business-settings.component.ts` generates a QR code client-side with the `qrcode` npm package, rendering it into a `<canvas #qrCanvas>` bound via `@ViewChild`, and exposes a `downloadQr()` that reads the canvas as a PNG data URL. There is no scan-tracking data source anywhere in the stack yet — the "Scans" section must render against data that doesn't exist.

## Goals / Non-Goals

**Goals:**
- Restructure `dashboard-home` into header / QR panel / stat cards / scans-shell sections per the new `dashboard-overview` spec.
- Deduplicate QR rendering so `business-settings` and `dashboard-home` share one implementation.
- Make the scans section resilient to "no data source yet" without special-casing a not-yet-built API.

**Non-Goals:**
- Implementing scan tracking, its endpoint, or its storage (companion change).
- Redesigning `business-settings` beyond extracting the QR piece.
- Adding the reference design's OS-breakdown panel.

## Decisions

- **Extract a shared `QrCodeComponent`** (`apps/web/src/app/shared/qr-code/qr-code.component.{ts,html,css}`) that takes a `value` (the public catalog URL) as an `@Input`, renders the canvas internally, and exposes a `download()` method plus a `qrReady` output/state for parent templates to gate the download button on. Rationale: avoids duplicating the `qrcode` library call and canvas lifecycle in two components; `business-settings` swaps its inline canvas binding for `<app-qr-code [value]="publicCatalogUrl">`, `dashboard-home` does the same. Alternative considered: leave QR logic only in `business-settings` and have `dashboard-home` link out to it instead of rendering its own — rejected because the reference layout (and the user's stated intent) puts the QR front-and-center on the landing screen itself.
- **Scans section reads from an injected, optional data source with a null-safe default.** Since no backend exists yet, `DashboardHomeComponent` will call a small `ScanStatsService` placeholder that, for this change, always resolves to `{ total: 0, byDay: [] }` (no HTTP call is made — this avoids either wiring a fake endpoint or leaving a dangling unimplemented call). The companion scan-tracking change replaces this service's implementation with a real API call; the component and template do not need to change when that happens because they already render the empty/zero state correctly. Alternative considered: hide the whole Scans section until the other change lands — rejected because the user wants the shell visible now and the spec requires an explicit empty state.
- **Stat cards keep using existing `BusinessApiService` / `CategoryApiService` / `ProductApiService` calls** — only the template/CSS structure changes, not the data-fetching, since the proposal is UI-only.
- **No new routes.** This stays the existing `dashboard-home` route; only its internal layout changes.

## Risks / Trade-offs

- [Introducing `ScanStatsService` now, ahead of the real tracking change] → Mitigation: keep its interface (`{ total, byDay }`) minimal and stable so the companion change only swaps the implementation, not callers.
- [Extracting `QrCodeComponent` touches `business-settings`, a screen outside this change's stated scope] → Mitigation: keep the extraction mechanical (same `qrcode` call, same canvas approach) and covered by `business-settings`' existing tests/manual QA path, not a behavior change for that screen.
