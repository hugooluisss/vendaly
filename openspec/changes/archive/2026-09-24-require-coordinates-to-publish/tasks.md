## 1. Backend

- [x] 1.1 In `BusinessService` (wherever `setPublished()`/publish logic lives), add a precondition: when setting `is_published = true`, require `latitude`/`longitude` to both be non-null; throw a clear `DomainException` otherwise. Unpublishing (`is_published = false`) is never gated by this. Verify unit tests cover: publish rejected without coordinates, publish succeeds with coordinates, unpublish always succeeds regardless of coordinates, and an already-published coordinate-less business (existing data) is unaffected by this check (i.e. the check only runs on the publish transition, not as a standing invariant re-validated on every read/update).

## 2. Frontend

- [x] 2.1 In `catalog-management.component` (where "Publicar catálogo" lives), disable the publish button (or otherwise block the action with a clear message) when the business has no coordinates set, pointing the owner to the "Ubicación en el mapa" field in business-settings.
- [x] 2.2 Handle the backend's rejection gracefully if the button is somehow clicked anyway (race condition between screens) — show the same clear message rather than a generic error.

## 3. Verification

- [ ] 3.1 Run the full backend test suite inside docker-api-1 and the Angular build/tests, confirm both green
- [ ] 3.2 Manual smoke test: create a fresh business with no coordinates, confirm publish is rejected/blocked; set coordinates, confirm publish now succeeds
