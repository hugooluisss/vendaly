## 1. Backend data model

- [x] 1.1 Add migration: nullable `category` (text, constrained via app-level validation to the fixed list) and nullable `location` (text) columns on `businesses`; verify it applies cleanly against the running Postgres
- [x] 1.2 Update `Business` entity with `category`/`location` properties

## 2. Backend profile + directory endpoints

- [x] 2.1 Extend `BusinessService::updateProfile()` to accept and validate `category` (must be one of the fixed list or null) and `location` (free text or null); verify unit tests cover a valid category, an invalid category (rejected), and both fields left unset
- [x] 2.2 Add `BusinessRepository::findPublishedDirectory(?category, ?location)` (category exact match, location case-insensitive substring match, both optional, published-only) and wire it into a new `GET /public/businesses` endpoint; verify unit tests cover unfiltered listing, category filter, location filter, and combined filters, each excluding unpublished businesses

## 3. Frontend

- [x] 3.1 Add a category picker (fixed list, Spanish labels) and a location text input to the business profile form in `business-settings.component`
- [x] 3.2 Replace `PublicComponent`'s placeholder template with the directory page: search controls (category dropdown, location text input) and a list of published businesses (logo, name, category, location, link to `/public/catalog/:slug`)
- [x] 3.3 Add an API service call for `GET /public/businesses` with optional category/location query params

## 4. Verification

- [x] 4.1 Run the full backend test suite inside docker-api-1 and the Angular build/tests, confirm both green
- [x] 4.2 Manual smoke test: set a category/location on the existing "Café Vendaly" business, confirm it appears in `/public` and is found by its category and by a location search term
