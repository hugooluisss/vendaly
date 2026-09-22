# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Vendaly is a SaaS for digital catalogs/menus shared via a public URL/QR code. Customers browse a business's catalog, build a selection, and send it to the business via a WhatsApp deep link (`wa.me`) — not the WhatsApp Business API. This is not e-commerce/POS: no payments, inventory, delivery, or invoicing in scope. A user is one business in the current model (`BusinessMember` exists for future multi-business/roles support but no multi-tenancy logic is implemented yet).

Monorepo: `apps/web` (Angular) + `apps/api` (PHP/Yii3), plus `openspec/` for spec-driven change planning and `docker/` for local orchestration.

## Commands

All commands assume the stack is running via `docker compose -f docker/docker-compose.yml up -d` (services: `api` on :8080 via FrankenPHP, `web` on :4200, `postgres` on :5432, `minio` for S3-compatible storage on :9000/:9001).

### Backend (`apps/api`)

Run everything through the `docker-api-1` container — the host PHP install may be missing extensions the project needs:

```bash
docker exec docker-api-1 vendor/bin/codecept run Unit                          # full unit suite
docker exec docker-api-1 vendor/bin/codecept run Unit RepositoriesTest         # one test class
docker exec docker-api-1 vendor/bin/codecept run Unit RepositoriesTest:"Active products block category soft delete"  # one test method
docker exec docker-api-1 php bin/migrate.php                                   # run pending Cycle ORM migrations
docker exec docker-api-1 vendor/bin/php-cs-fixer fix                           # code style
docker exec docker-api-1 vendor/bin/rector process                             # automated refactors
docker exec docker-api-1 vendor/bin/psalm                                      # static analysis
```

Test suites are Codeception (`Unit`, `Functional`, `Console`, `Web`, configured in `codeception.yml` / `tests/*.suite.yml`), with PHPUnit-style test classes under `tests/Unit/`. **There is no separate test database** — `DATABASE_URL` in `docker-compose.yml` points the test run at the same Postgres instance the app uses, so running the suite leaves behind rows in `businesses`/`users`/etc. Expect (and don't be surprised by) leftover `Test business`, `DirectoryTest*`, `geo-*`, `Smoke *` rows in the dev DB from prior runs.

### Frontend (`apps/web`)

```bash
npm start                                    # ng serve on :4200
npm run build                                # ng build
npm test                                     # ng test (Karma/Jasmine)
npx ng test --include='**/some.component.spec.ts'  # one spec file
```

If you add an npm package while the stack is running in Docker, the `web` service's anonymous `node_modules` volume goes stale — recreate it:
```bash
docker compose -f docker/docker-compose.yml down web && docker compose -f docker/docker-compose.yml up -d --build --force-recreate -V web
```

### OpenSpec

This repo uses the `spec-driven` OpenSpec schema (`openspec/config.yaml`) for planned changes: `proposal.md`, `specs/<capability>/spec.md` (delta), optional `design.md`, `tasks.md`. Capabilities live under `openspec/specs/<capability>/spec.md`; completed changes move to `openspec/changes/archive/YYYY-MM-DD-<name>/`. Use the `openspec-propose` / `openspec-apply-change` / `openspec-archive-change` skills (or `/openspec-*` commands) rather than hand-editing the change lifecycle.

## Architecture

### Backend layering (PHP / Yii3 / Cycle ORM)

Strict **controller → service → repository** layering — controllers depend only on services, never on repository interfaces directly:

- `src/Web/<Feature>/` — HTTP controllers/actions (thin; route → service call → JSON response). Routes are centrally registered in `apps/api/config/common/routes.php`, most under `AccessTokenMiddleware` (JWT bearer auth) except `/public/*`, `/auth/*`, `/health`.
- `src/Application/` and `src/Domain/Service/` — business logic (e.g. `AuthService`, `BusinessService`, `CatalogService`, `OrderService`). Keep methods close to single-responsibility.
- `src/Domain/Entity/` — Cycle ORM entities (data-mapper style, annotation-based `#[Entity]`/`#[Column]`). **Entities must not be declared `final`** — Cycle's proxy/hydration needs to subclass them.
- `src/Domain/Repository/` — repository *interfaces* (the contract services depend on).
- `src/Infrastructure/Cycle/Repository/` — Cycle ORM implementations of those interfaces.
- `src/Infrastructure/Storage/` — S3-compatible object storage (MinIO locally) for logos/cover images.

Watch for N+1 patterns in repository implementations — batch with `WHERE id IN (...)` rather than looping single-row queries.

Auth is JWT (access + refresh tokens, not cookie sessions), chosen because the Angular SPA and PHP API are separate origins/deployments. `AuthService::register/login` uses `password_hash`/`password_verify` (bcrypt via `PASSWORD_DEFAULT`); refresh tokens are stored hashed (`sha256`) with expiry/revocation in `refresh_tokens`.

Migrations are plain Cycle ORM migration files under `apps/api/migrations/`, run via `bin/migrate.php` (see Commands above) — not through the `yii` console app.

### Frontend structure (Angular, standalone components)

- `app.routes.ts` splits into two lazily-loaded route trees: `dashboard/` (business owner, behind `authGuard`) and `public/` (customer-facing, no auth) — default redirect is `public`.
- `dashboard/` — owner-facing: auth (`auth/auth-form`), onboarding, business settings, catalog management, order history.
- `public/` — customer-facing: business directory (`public/directory`), a business's catalog page (`public/catalog-page`), order summary/WhatsApp handoff (`public/order-summary`).
- `shared/` — cross-feature components (`modal`, `map` (Leaflet + OpenStreetMap tiles, no paid API key), `image-upload`) and `business-category.ts` (the fixed category list used for filtering/labels across dashboard and public).
- `auth/auth.interceptor.ts` attaches the JWT bearer token to API requests and transparently retries once via `/auth/refresh` on a 401 (redirecting to login only if refresh also fails).
- Component convention: each component gets its own folder with three separate files (`.component.ts`, `.component.html`, `.component.css`) — no inline `template:`/`styles:` in the `@Component` decorator.
- Styling: CSS custom properties in `apps/web/src/styles.css` — orange primary (`#EA580C`/`#F97316`), the "Enviar por WhatsApp" CTA deliberately stays WhatsApp's official green rather than the orange brand color so it doesn't visually blend with native WhatsApp UI.

### Public business directory (`public/directory`)

A single, unified (non-grouped) list of published businesses, filterable by floating multi-select category chips (`shared/business-category.ts`'s fixed list) and a debounced name search, both client-side over one API response (`GET /public/businesses`, no category param sent — filtering happens in the component). Infinite scroll reveals results in batches via `IntersectionObserver`. A Leaflet map renders pins for every matching business with coordinates, independent of how many list items are currently revealed by pagination. Distance-based sorting/proximity search is server-side (haversine, no external distance API), used when the browser grants geolocation.

## Workflow conventions

- Git commits in this repo are GPG-signed by the developer — commits should be prepared (staged + message) but run via a signing-capable terminal the developer controls, not executed non-interactively on their behalf.
- Never add AI/session attribution to commits or PR descriptions in this project.
