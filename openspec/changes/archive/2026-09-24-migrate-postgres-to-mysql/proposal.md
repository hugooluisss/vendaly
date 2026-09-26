# Proposal

## Why

Vendaly's API currently requires PostgreSQL, which narrows future hosting choices — cheap shared hosting and many budget VPS images default to (or only offer) MySQL/MariaDB. Switching the persistence layer to MySQL 8 now, while the schema is still small and the environment is dev-only, removes that constraint before deployment planning starts.

## What Changes

- Swap Cycle DBAL's driver configuration from `PostgresDriverConfig` to `MySQLDriverConfig` (`apps/api/src/Infrastructure/Cycle/CycleOrm.php`), reading `DATABASE_URL` (now a `mysql:` DSN), `DB_USER`, `DB_PASSWORD` (renamed from `POSTGRES_USER`/`POSTGRES_PASSWORD`).
- Rewrite every migration's Postgres-only SQL to MySQL-compatible equivalents (`BIGSERIAL`→`BIGINT AUTO_INCREMENT`, `TIMESTAMPTZ`→`TIMESTAMP`, the order-status migration's `UPDATE...FROM`→`UPDATE...JOIN` and `ALTER COLUMN...SET NOT NULL`→`MODIFY COLUMN`).
- Rewrite the four repository methods that use Postgres-only SQL (`ON CONFLICT...RETURNING`, `UPDATE...RETURNING`, `ILIKE`, `to_char`/`date_trunc`) to their MySQL equivalents, preserving identical observable behavior.
- Remove the `postgres` service and `postgres_data` volume from `docker/docker-compose.yml`; the `api` service instead connects to a MySQL 8 server already running natively on the host (`mysql.service`, reconfigured to `bind-address = 0.0.0.0`) via `host.docker.internal`, requiring an `extra_hosts` entry.
- Source `DATABASE_URL`/`DB_USER`/`DB_PASSWORD` for the `api` service from `docker/.env` (gitignored, following the existing `WEB_SERVE_FLAGS`/`LOCAL_UID` pattern) rather than hardcoding them in the committed `docker-compose.yml`.
- No data migration: this is a dev-only environment with test/seed data; Cycle's migrations recreate the schema from scratch against the already-created, empty `dev_vendaly` database (and `vendaly_app` user, already provisioned on the host).

## Capabilities

This is a pure infrastructure/persistence-layer swap: every existing capability (`business-management`, `catalog-management`, `public-catalog`, `business-directory`, `order-intent`, `order-history`, `user-auth`) must behave identically before and after — the database engine is an implementation detail, not spec-level behavior. No requirement in any existing spec changes, and no new capability is introduced. This change sets `skip_specs: true` in `.openspec.yaml` (matching the existing `angular-component-file-structure` change) and carries no `specs/` delta.

## Impact

- **Backend**: `apps/api/src/Infrastructure/Cycle/CycleOrm.php` (driver swap), all ~13 files under `apps/api/migrations/` (syntax rewrite), 3 repository classes under `apps/api/src/Infrastructure/Cycle/Repository/` (`CycleBusinessRepository`, `CycleCustomerRepository`, `CycleOrderRepository`, `CycleCatalogScanRepository` — 4 files) with Postgres-specific raw SQL.
- **Infra**: `docker/docker-compose.yml` (remove `postgres` service/volume, add `extra_hosts` to `api`, source DB env vars from `docker/.env`); `docker/.env` and `apps/api/.env` already updated with the new connection details (outside this change, done ahead of time as a prerequisite).
- **Tests**: the full Codeception `Unit` suite (currently 96 tests) must pass unmodified in assertions against MySQL — any test that needed a behavior-preserving adjustment to its setup (not its assertions) should be called out during implementation, not silently changed.
- **No breaking changes to callers**: `CatalogService`, `BusinessService`, `OrderService`, and all controllers are unaffected — only the repository implementations and raw SQL beneath them change.
