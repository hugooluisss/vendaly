# Tasks

## 1. Backend data layer

- [ ] 1.1 Add migration under `apps/api/migrations/` creating `catalog_scans` (`id BIGSERIAL PRIMARY KEY`, `business_id BIGINT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE`, `created_at TIMESTAMP NOT NULL DEFAULT now()`) with an index on `business_id`; verify with `docker exec vendaly-api-1 php bin/migrate.php` applying cleanly.
- [ ] 1.2 Add `App\Domain\Entity\CatalogScan` (non-`final`, Cycle `#[Entity]`/`#[Column]` annotations matching the migration columns); verify the entity is picked up by running the Unit suite (no errors on schema compile).
- [ ] 1.3 Add `App\Domain\Repository\CatalogScanRepositoryInterface` with `record(int $businessId): void`, `countTotal(int $businessId): int`, and `countByDay(int $businessId, DateTimeImmutable $from, DateTimeImmutable $to): array` (returns `[{date, count}]`); verify via a Unit test in `tests/Unit/RepositoriesTest.php` (or a new test class) covering record + count behavior.
- [ ] 1.4 Add `App\Infrastructure\Cycle\Repository\CatalogScanRepository` implementing the interface, following the existing Cycle repository pattern; verify the same repository test passes against the real Cycle-backed implementation (per `codeception.yml` Unit suite wiring).

## 2. Backend service and endpoints

- [ ] 2.1 Add a service method (e.g. on `CatalogService` or a new `ScanTrackingService`) to record a scan by business slug (resolving via `BusinessRepositoryInterface::findPublishedBySlug`, 404 semantics if not found) and to fetch total/per-day stats by business id; verify with a Unit test covering both the found and not-found slug cases.
- [ ] 2.2 Add `POST /public/catalog/{slug}/scans` (unauthenticated, outside `AccessTokenMiddleware`) registered in `apps/api/config/common/routes.php`, calling the record service method and returning 204/404; verify with a Functional test posting to the route with a valid and an invalid slug.
- [ ] 2.3 Add `GET /businesses/{id}/scans` (behind `AccessTokenMiddleware`, ownership-checked like other `BusinessController`/`CatalogController` owner routes) returning `{ total, by_day }` for an optional date range (default: a sensible recent window, e.g. last 30 days); verify with a Functional test covering: owner fetching their own stats, a non-owner being denied, and zero-scans returning an empty `by_day`.

## 3. Frontend QR marker

- [ ] 3.1 Update wherever the catalog URL is encoded into the QR (the shared `QrCodeComponent`/`business-settings.component.ts` from `redesign-dashboard-home-ui` if merged, otherwise `business-settings.component.ts` directly) to append `?src=qr` to the URL passed into `QRCode.toCanvas`; verify by downloading the QR and confirming the encoded URL includes the marker (manual check via a QR-reading app or by decoding in a test).

## 4. Frontend scan recording and stats consumption

- [ ] 4.1 Update `catalog-page.component.ts` to check `route.snapshot.queryParamMap.get('src') === 'qr'` on init and, if present, call a new `CatalogApiService` (or dedicated) method that POSTs to `/public/catalog/{slug}/scans`, swallowing errors (fire-and-forget); verify with a component/service unit test asserting the POST fires only when the marker is present.
- [ ] 4.2 Update `ScanStatsService` (from `redesign-dashboard-home-ui`, or add it here if that change hasn't landed yet) to call `GET /businesses/{id}/scans` and map the response into `{ total, byDay }`; verify with a unit test mocking the HTTP call and asserting the mapped shape.

## 5. Verification

- [ ] 5.1 Run the backend Unit and Functional suites (`docker exec vendaly-api-1 vendor/bin/codecept run Unit` and `... run Functional`) and confirm the new tests pass alongside existing ones.
- [ ] 5.2 Manually verify end-to-end: scan (or manually visit with `?src=qr`) a business's public catalog URL, then confirm the dashboard's Scans section (from `redesign-dashboard-home-ui`) shows a non-zero total and the correct day bucket.
