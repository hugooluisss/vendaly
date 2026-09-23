# Tasks

## 1. Shared QR code component

- [x] 1.1 Create `apps/web/src/app/shared/qr-code/qr-code.component.{ts,html,css}` as a standalone component taking a `value: string | undefined` input, rendering via the `qrcode` package into an internal canvas, exposing `qrReady` state and a `download(filename: string)` method; verify by unit test (`npx ng test --include='**/qr-code.component.spec.ts'`) covering: no value → no render attempt, valid value → `qrReady` becomes true, `download()` triggers a link click only when ready.
- [x] 1.2 Update `business-settings.component.ts/.html` to use `<app-qr-code>` instead of the inline `@ViewChild('qrCanvas')`/`QRCode.toCanvas` logic, keeping the existing "Descargar QR" button wired to the component's `download()`; verify manually in the running app (`docker compose -f docker/docker-compose.yml up -d`, business settings page still shows and downloads the QR).

## 2. Scan stats placeholder

- [x] 2.1 Add `apps/web/src/app/dashboard/scan-stats.service.ts` exposing `getStats(businessId: number): Observable<{ total: number; byDay: { date: string; count: number }[] }>`, returning `of({ total: 0, byDay: [] })` for now (no HTTP call); verify with a unit test asserting the empty result shape.

## 3. Dashboard-home restructure

- [x] 3.1 Rewrite `dashboard-home.component.html`/`.css` into four sections — business header (name + published/unpublished), QR panel (`<app-qr-code>` + download action, hidden/empty-stated when no public URL), stat cards row (categories/products/active/inactive, reusing existing bound fields), and a Scans section (total tile + per-day chart area) — using `--vendaly-*` CSS custom properties for all colors; verify visually via `npm start` at `/dashboard` for a business with and without a public slug.
- [x] 3.2 Update `dashboard-home.component.ts` to inject `ScanStatsService`, call it once the business is loaded, and expose `scanTotal`/`scanByDay` (or equivalent) to the template with a `hasScans` flag driving the empty state; verify with a component unit test asserting the empty-state renders when `byDay` is empty and the chart area renders when it isn't.
- [x] 3.3 Verify the "no business yet" path (existing `@if (!business)` branch) still shows the onboarding prompt and none of the new header/QR/stats/scans sections; verify manually by hitting `/dashboard` with a user that has no business.

## 4. Verification

- [x] 4.1 Run `npm test` (or the targeted specs above) and confirm all dashboard-home, qr-code, and scan-stats specs pass.
- [x] 4.2 Manually walk through `/dashboard` in the browser for: a published business (QR + stats + empty scans), an unpublished business (no QR, prompt instead), and a fresh account with no business (onboarding prompt only) — confirm no console errors.
