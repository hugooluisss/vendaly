## Context

Greenfield repo (see proposal.md - Why). Stack decided with the user: Angular frontend, PHP 8 / Yii 3 backend, Cycle ORM (data-mapper) for persistence, PostgreSQL, S3-compatible object storage, single monorepo. Backend must strictly layer controller → service → repository: controllers depend only on services, never on repositories directly. Single business per user in this version (`BusinessMember` modeled but no roles/multi-tenancy logic yet). Auth is JWT (access + refresh), chosen because the Angular SPA and PHP API are separate origins.

## Goals / Non-Goals

**Goals:**
- Define the monorepo layout and how `apps/web` and `apps/api` are wired together and to Postgres/S3 via Docker Compose.
- Define the controller/service/repository boundary concretely for Yii 3 + Cycle ORM, including where DTO/validation lives.
- Define the entity/table shape for `User`, `Business`, `BusinessMember`, `Catalog`, `Category`, `Product`, `ProductImage`, `Order`, `OrderItem`, in a way that leaves room for `ProductOption`/`ProductOptionValue` later.
- Define slug generation, JWT issuance/verification, and WhatsApp message/deep-link generation as concrete algorithms.

**Non-Goals:**
- Multi-business-per-user, roles/permissions beyond "is a member of this business" (post-MVP).
- Product variants/options implementation (schema readiness only, per catalog-management spec).
- Anything from the handoff's "Future Direction" list (themes, custom domains, analytics dashboard, WhatsApp Business API, payments).

## Decisions

### Monorepo layout
```
vendaly/
├── apps/
│   ├── web/     # Angular workspace: dashboard (auth) + public catalog (no auth), lazy-loaded
│   └── api/     # Yii 3 application
├── docker/
│   └── docker-compose.yml   # api, web (dev server), postgres, (local S3-compatible: minio)
├── openspec/
└── README.md
```
One Angular app with two lazy-loaded feature areas (`dashboard/*` behind an auth guard, `public/*` open) rather than two separate Angular projects — avoids duplicating build config/shared UI for an MVP this size. Split into separate apps later only if the two experiences' release cadence or team ownership actually diverges.

### Backend layering (Yii 3 + Cycle ORM)
- **Controller**: HTTP-only concerns — decode request, call exactly one service method, map the result/exception to an HTTP response. No business logic, no direct Cycle repository access.
- **Service**: business logic, one class per capability area (e.g. `AuthService`, `BusinessService`, `CatalogService`, `OrderService`). A service method should do one thing (validate/authorize a specific rule, or orchestrate a single use case) — split a service into smaller collaborators before letting a method grow multiple responsibilities.
- **Repository**: one interface + Cycle-backed implementation per aggregate root (`UserRepository`, `BusinessRepository`, `CategoryRepository`, `ProductRepository`, `OrderRepository`). Repositories return domain entities, never raw Cycle query builders, to the service layer.
- Wiring: Yii 3's DI container binds repository interfaces to their Cycle implementations; controllers receive services via constructor injection, services receive repositories the same way.

### Data model
- `User(id, email, password_hash, created_at)`
- `Business(id, owner_user_id, name, slug UNIQUE, logo_url, whatsapp_number, description, is_published, created_at)`
- `BusinessMember(id, business_id, user_id, created_at)` — exists now purely as the join table Business↔User; no `role` column until multi-member support is actually needed, add it then rather than now.
- `BusinessHours(id, business_id, day_of_week 0-6, opens_at, closes_at, is_closed)`
- `Catalog` is represented as an attribute of `Business` (`is_published`) rather than a separate table in this version — one business has exactly one implicit catalog (its categories+products). Introduce a standalone `Catalog` entity only if/when multi-catalog-per-business becomes real scope; forcing it in now duplicates `Business` for no behavioral gain yet.
- `Category(id, business_id, name, position)`
- `Product(id, business_id, category_id, name, description, price NULLABLE, is_active, position, created_at)` — `price` nullable satisfies "no price" products. No `product_option_group_id`/variant columns yet, but nothing in this shape blocks adding a `ProductOptionGroup`/`ProductOption`/`ProductOptionValue` set of tables later with a FK back to `Product`.
- `ProductImage(id, product_id, url, created_at)` — modeled as its own table (1:1 for now) rather than a `Product.image_url` column, since the proposal explicitly lists `ProductImage` as a core entity and this keeps the door open to multiple images later without a migration that changes `Product`'s shape.
- `Order(id, business_id, customer_note NULLABLE, total NULLABLE, created_at)`
- `OrderItem(id, order_id, product_id, product_name_snapshot, unit_price_snapshot NULLABLE, quantity, note NULLABLE)` — snapshots of name/price at order time so a later product edit doesn't retroactively alter historical orders.

### Slug generation
Slugify the business name (lowercase, ASCII, hyphens); on collision append `-2`, `-3`, etc. until unique. Done once at business creation; not regenerated on later name edits (a stable public URL matters more than it matching the current name).

### Auth (JWT)
- Access token: short-lived (15 min), signed HS256, carries `user_id`.
- Refresh token: longer-lived (30 days), opaque random value stored hashed in a `refresh_tokens` table (so a stolen DB dump can't be replayed as tokens) with `user_id`, `expires_at`, `revoked_at`. Rotated on each use (old one revoked, new one issued) to limit replay window.
- Yii 3 middleware validates the access token and attaches the resolved `User` to the request for controllers/services that need it.

### WhatsApp message generation
A pure function (no I/O) takes an `Order` + its `OrderItem`s and the business's `whatsapp_number`, and returns `wa.me/<number>?text=<url-encoded message>`. Message template: one line per item (`{qty}x {name} — {price}` or `{qty}x {name}` if priceless), blank line, `Total: {sum}` omitted entirely if every item is priceless. Kept as a pure function so it's independently testable without hitting the database.

## Risks / Trade-offs

- **No `role` on `BusinessMember` yet** → if multi-member support lands later, this needs a migration adding `role` and enforcing it in `BusinessService`; acceptable now since MVP is explicitly single-owner.
- **`Catalog` folded into `Business.is_published`** → if "multiple catalogs per business" becomes real scope, extracting a `Catalog` entity means a migration moving `category.business_id`/`product.business_id` to point at a new `catalog_id`; flagged in proposal as post-MVP, so deferred deliberately.
- **JWT access token is stateless (15 min)** → a user disabled mid-session keeps API access for up to 15 minutes; mitigated by the refresh token rotation table, which can be revoked immediately to block renewal.
- **Order total computed and stored, not derived live** → if product prices change after an order, the historical order stays correct via `unit_price_snapshot`; mitigation is already in the schema, not a follow-up.

## Open Questions

None — the remaining ambiguities (multi-business, auth mechanism, brand color) were resolved with the user before writing this design.
