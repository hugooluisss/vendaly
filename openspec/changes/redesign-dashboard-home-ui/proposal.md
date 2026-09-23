# Proposal

## Why

`dashboard-home` is currently a bare summary (business name, four small stat numbers, two text links) that gives the owner no sense of their catalog's public-facing presence. The owner's most useful actions — checking the QR code, seeing whether the catalog is published, jumping into settings or the product list — are scattered or missing from the landing screen. Restructuring it as a single "catalog overview" screen (header, prominent QR panel, stat cards, and a scans section) gives owners one place to see their catalog's status and reach it, following the layout proven by QR-analytics dashboards (reference: a QR-code generator's item-detail/analytics page).

## What Changes

- Restructure `dashboard-home` into: (1) a business header card showing name, published/unpublished status, and slug/public URL; (2) a QR panel with a visible QR code and "download" / "edit catalog" actions; (3) a stat-cards row (categories, products, active/inactive — same data as today, restyled); (4) a "Scans" section shell with a per-day bar-chart area and a total-count tile.
- Extract the QR-generation logic currently inline in `business-settings.component.ts` (`qrcode` library usage + canvas rendering + download) into a shared, reusable component (e.g. `shared/qr-code/qr-code.component`) so `dashboard-home` and `business-settings` both render the same QR from the business's public catalog URL without duplicating the `qrcode` integration.
- The "Scans" section ships as a UI shell only: an empty state ("Aún no hay escaneos" or similar) plus the chart/total layout wired to a data source that does not exist yet. It renders whatever the (currently absent) scan-count API returns, defaulting to zero/empty — no scan tracking, endpoint, or storage is added by this change.
- No "Operating System" breakdown (present in the reference design) is included — out of scope per product decision; only total + per-day counts are planned, and only in the companion scan-tracking change.
- Visual style follows Vendaly's existing brand tokens (`--vendaly-orange` / `--vendaly-orange-light`, `--vendaly-surface`, etc. from `apps/web/src/styles.css`) — the reference design's blue/teal palette is not carried over, only its structural layout (header → QR/preview panel → stat cards → chart section).

## Capabilities

### New Capabilities
- `dashboard-overview`: Describes the business owner's landing/summary screen — what status and catalog information it must surface, and that it must expose the catalog's QR code and a (possibly-empty) scan-activity view.

### Modified Capabilities
_None._ No existing capability's requirements change — `business-management`'s QR/public-URL behavior is unchanged (only where the QR is rendered from), and `catalog-management`'s product/category counts are read, not altered.

## Impact

- **Frontend**: `apps/web/src/app/dashboard/dashboard-home/` (component/template/styles rewritten), `apps/web/src/app/dashboard/business-settings/` (QR logic extracted out), new `apps/web/src/app/shared/qr-code/` component.
- **Backend**: none. No new endpoints, migrations, or entities.
- **Dependency**: the "Scans" section's real numbers depend on a separate, not-yet-implemented change (basic QR scan tracking via a query param) — this change must not block on it and must degrade to an empty/zero state until that data exists.
