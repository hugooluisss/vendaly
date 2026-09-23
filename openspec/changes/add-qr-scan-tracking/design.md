# Design

## Context

The public catalog is served by `App\Web\PublicCatalog\Action` → `CatalogService::publicCatalog()` → `BusinessRepositoryInterface::findPublishedBySlug()`, following the controller → service → repository layering (see CLAUDE.md - Architecture). Public, unauthenticated endpoints (`/public/catalog/{slug}`, `/public/businesses`, `/public/orders`) sit outside `AccessTokenMiddleware` in `apps/api/config/common/routes.php`; owner-facing endpoints (business/catalog management) sit behind it. Migrations are hand-written SQL in Cycle `Migration` subclasses under `apps/api/migrations/`, run via `bin/migrate.php`. On the frontend, the QR is currently generated inline in `business-settings.component.ts` (`QRCode.toCanvas(canvas, publicCatalogUrl)`); `redesign-dashboard-home-ui` proposes extracting this into a shared `QrCodeComponent` and a placeholder `ScanStatsService`. See proposal.md for why this change adds real data behind that placeholder.

## Goals / Non-Goals

**Goals:**
- Record a scan (business id + timestamp) when the public catalog loads via the QR code, with no visitor-identifying data.
- Let the owner fetch total + per-day scan counts for their business.
- Keep this independent of whether `redesign-dashboard-home-ui` has landed — the record/query endpoints and QR marker stand on their own.

**Non-Goals:**
- Unique-visitor tracking, deduplication, or rate-limiting.
- OS/device/browser detection.
- Any change to the public catalog response shape (categories/products/etc. are untouched).

## Decisions

- **New entity `CatalogScan`** (`src/Domain/Entity/CatalogScan.php`) mapping to a `catalog_scans` table: `id BIGSERIAL`, `business_id BIGINT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE`, `created_at TIMESTAMP NOT NULL DEFAULT now()`. No visitor/session columns — matches the "no PII" requirement directly at the schema level rather than relying on application code to omit it.
- **Day-level aggregation happens at query time, not write time.** Store one row per scan with a full timestamp; `CatalogScanRepositoryInterface::countByDay(int $businessId, DateTimeImmutable $from, DateTimeImmutable $to): array` runs a `GROUP BY date_trunc('day', created_at)` query. Rationale: keeps the write path trivial (a single insert) and leaves room for finer-grained queries later without a schema change. Alternative considered: pre-aggregate into a `catalog_scan_daily_counts` table incremented on write — rejected as premature at this scan volume (a menu-QR use case, not high-traffic analytics) and it would need its own migration/backfill logic for no current benefit.
- **Recording endpoint resolves the business by slug, not by numeric id**, mirroring `/public/catalog/{slug}` — the frontend already has the slug from the current route, and never exposing raw business ids on the public catalog page avoids leaking an enumerable identifier. Route: `POST /public/catalog/{slug}/scans`, unauthenticated, in a new `App\Web\PublicCatalogScan` (or added to `PublicCatalog`) action, returning 404 for an unknown/unpublished slug (matching the existing catalog-lookup behavior) and 204 on success.
- **Query endpoint is owner-scoped by business id**, consistent with other authenticated business endpoints (`GET /businesses/{id}/...` behind `AccessTokenMiddleware`, ownership checked via `BusinessMember` the same way `CatalogController` does for categories/products). Route: `GET /businesses/{id}/scans?from=&to=` returning `{ total: int, by_day: [{ date: string, count: int }] }` — the exact shape the frontend `ScanStatsService` already expects from `redesign-dashboard-home-ui`.
- **QR marker is a plain query parameter (`?src=qr`) on the encoded URL**, not a path segment or separate short-link — keeps `findPublishedBySlug()` and Angular's existing `/public/catalog/:slug` route untouched; the frontend route already reads `route.snapshot.paramMap.get('slug')` and can additionally read `queryParamMap.get('src')` without any routing changes.
- **The frontend fires the record call once per page load** (in `catalog-page.component.ts`'s constructor/init, guarded by `route.snapshot.queryParamMap.get('src') === 'qr'`), fire-and-forget (no UI feedback, errors are swallowed) — recording a scan must never block or degrade the customer's catalog browsing experience.

## Risks / Trade-offs

- [No dedup means one visitor scanning repeatedly, or a bot re-fetching the URL, inflates counts] → Mitigation: explicitly documented as an accepted limitation (proposal.md); acceptable because the feature's purpose is a rough usage signal, not billing- or decision-critical analytics. Revisit only if owners report the numbers as misleading.
- [`?src=qr` is trivially forgeable by anyone sharing the catalog link with that param] → Mitigation: accepted — this isn't a security boundary, just a best-effort attribution signal; no authorization or business logic depends on it.
- [Adding a query param to the QR-encoded URL changes what "the QR code" the `public-catalog` spec already promises] → Mitigation: handled explicitly as a MODIFIED requirement in this change's `public-catalog` spec delta rather than silently changing behavior underneath the existing spec.
