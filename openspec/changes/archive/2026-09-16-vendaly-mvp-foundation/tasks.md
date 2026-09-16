## 1. Repo & infra scaffolding

- [x] 1.1 Create `apps/api` (Yii 3 skeleton via Composer) and `apps/web` (Angular workspace) under the monorepo layout in design.md; verify each runs its respective "hello world" (`apps/api` responds on a health route, `apps/web` serves the default Angular page)
- [x] 1.2 Add `docker/docker-compose.yml` wiring `api`, `web`, `postgres`, and a local S3-compatible service (minio); verify `docker compose up` brings up all four and `api` can connect to `postgres`
- [x] 1.3 Configure Cycle ORM in `apps/api` (schema/migrations tooling) pointed at the compose Postgres instance; verify a trivial migration runs successfully

## 2. Data model

- [x] 2.1 Define Cycle entities and migrations for `User`, `Business`, `BusinessMember`, `BusinessHours` per design.md's Data model section; verify migrations apply cleanly and round-trip a manually inserted row through the entity
- [x] 2.2 Define Cycle entities and migrations for `Category`, `Product`, `ProductImage`; verify a `Product` with a null `price` and a `Product` with a set `price` both persist and load correctly
- [x] 2.3 Define Cycle entities and migrations for `Order`, `OrderItem` including the price/name snapshot fields; verify an `Order` with mixed priced/priceless `OrderItem`s persists and loads with snapshots intact
- [x] 2.4 Define repository interfaces + Cycle-backed implementations for each aggregate (`UserRepository`, `BusinessRepository`, `CategoryRepository`, `ProductRepository`, `OrderRepository`); verify each has a unit test covering create + fetch-by-id

## 3. Auth (user-auth capability)

- [x] 3.1 Implement `AuthService` register/login logic (password hashing, duplicate-email rejection) per specs/user-auth/spec.md; verify unit tests cover both scenarios in "Account registration" and "Login issues a token pair"
- [x] 3.2 Implement JWT access/refresh token issuance and the `refresh_tokens` table (hashed storage, rotation on use) per design.md's Auth section; verify a test exercises issue → use → rotate → old-token-rejected
- [x] 3.3 Implement the access-token-required middleware and wire it in front of authenticated controllers; verify a request without/with an expired token to a protected route returns unauthorized
- [x] 3.4 Build Angular auth: register/login forms, token storage, HTTP interceptor attaching the access token and handling refresh-on-401; verify manual login/logout flow works against the running API

## 4. Business management (business-management capability)

- [x] 4.1 Implement `BusinessService`/`BusinessController`: create business (with slug generation + collision handling), enforce single-business-per-user, per specs/business-management/spec.md; verify unit tests cover creation, slug collision, and "user already owns a business"
- [x] 4.2 Implement business profile update (name, logo upload to S3-compatible storage, WhatsApp number validation, description) and opening-hours configuration (including "closed" days); verify unit tests cover valid update, invalid WhatsApp number, and setting/closing a day
- [x] 4.3 Implement the "only the owning member can manage" authorization check as a reusable guard used by business and catalog controllers; verify a test asserts a non-member gets a forbidden error
- [x] 4.4 Build the Angular dashboard: business creation wizard and profile/hours settings screens; verify a manual walkthrough creates a business and edits its profile/hours end to end

## 5. Catalog management (catalog-management capability)

- [x] 5.1 Implement `CatalogService`/category CRUD + reordering per specs/catalog-management/spec.md; verify unit tests cover create, reorder, and the defined category-deletion-with-products behavior
- [x] 5.2 Implement product CRUD (with/without price, availability toggle) and product image upload/replace against S3-compatible storage; verify unit tests cover create-with-price, create-without-price, availability toggle, and image replace
- [x] 5.3 Implement catalog publish/unpublish on `Business.is_published`; verify a test confirms an unpublished catalog is not reachable via the public lookup used in section 6
- [x] 5.4 Build the Angular dashboard: category and product management screens (create/edit/delete, image upload, publish toggle); verify a manual walkthrough builds a small catalog and publishes it

## 6. Public catalog (public-catalog capability)

- [x] 6.1 Implement `GET /public/catalog/:slug` returning business info, hours, categories, and only active products, per specs/public-catalog/spec.md; verify unit tests cover published-with-mixed-availability and unpublished/unknown-slug scenarios
- [x] 6.2 Implement authenticated QR-code generation for the business's public catalog URL; verify a test decodes the generated QR image back to the expected URL
- [x] 6.3 Build the Angular mobile-first public catalog page (categories, product list, opening hours display) consuming the endpoint from 6.1; verify manual load of a published catalog on a mobile viewport

## 7. Order intent (order-intent capability)

- [x] 7.1 Implement client-side cart state in Angular (add/remove/quantity/notes) per specs/order-intent/spec.md; verify a manual walkthrough builds a selection with a priced and a priceless item and correct subtotal/total behavior
- [x] 7.2 Implement `POST /public/orders` (`OrderService`) persisting `Order`/`OrderItem`s with snapshots, rejecting empty selections and unpublished-slug targets; verify unit tests cover successful creation, empty selection, and unpublished slug
- [x] 7.3 Implement the pure WhatsApp message/deep-link generator per design.md's "WhatsApp message generation" decision; verify unit tests cover priced-only, mixed priced/priceless, and missing-WhatsApp-number scenarios
- [x] 7.4 Wire the Angular order summary screen: review selection → submit to 7.2 → redirect to the `wa.me` link from 7.3; verify an end-to-end manual walkthrough from adding items to landing on a pre-filled WhatsApp chat

## 8. Cross-cutting verification

- [x] 8.1 Run the full manual flow from the proposal's Core User Flow (owner creates account → business → categories/products → publishes → customer opens URL → selects items → sends via WhatsApp) against the docker-compose stack end to end; verify each step matches its spec scenarios
