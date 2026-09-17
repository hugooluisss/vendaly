## 1. Backend data model

- [x] 1.1 Add migration: nullable `latitude`/`longitude` (decimal) columns on `businesses`; verify it applies cleanly against the running Postgres
- [x] 1.2 Update `Business` entity with `latitude`/`longitude` properties

## 2. Backend profile + directory

- [x] 2.1 Extend `BusinessService::updateProfile()` to accept and validate coordinates (both present or both absent; latitude in [-90, 90], longitude in [-180, 180]); verify unit tests cover valid coordinates, an out-of-range value (rejected), and one-without-the-other (rejected)
- [x] 2.2 Extend `BusinessRepository::findPublishedDirectory()` with an optional visitor `(lat, lng)` parameter: when given, compute haversine distance in SQL against businesses that have coordinates and order by it ascending; businesses without coordinates are excluded only from this ordering, not from the base listing; verify unit tests cover nearest-first ordering, exclusion of coordinate-less businesses when sorting by distance, and that omitting the visitor position preserves existing (non-distance) behavior
- [x] 2.3 Include `latitude`/`longitude` (or null) in the `GET /public/businesses` response for every business

## 3. Frontend

- [x] 3.1 Add `leaflet` (npm) and a shared Angular map component wrapping it, supporting a single draggable pin mode (for the owner) and a multi-pin read-only mode (for the directory)
- [x] 3.2 In `business-settings.component`, add the map in single-pin mode so the owner can set/move their business's coordinates, wired to the profile save
- [x] 3.3 In the public directory (`public.component`), request the visitor's position via `navigator.geolocation`; on success, pass it to the directory API call and show a map with pins for businesses that have coordinates, alongside the existing list; on denial/error/unsupported, skip the map/position and keep the existing category/text-location search working exactly as before

## 4. Verification

- [x] 4.1 Run the full backend test suite inside docker-api-1 and the Angular build/tests, confirm both green
- [x] 4.2 Manual smoke test: pin a coordinate on the existing "Café Vendaly" business, confirm `GET /public/businesses` returns it with coordinates and that a visitor-position request nearest that pin ranks it first among businesses with coordinates
