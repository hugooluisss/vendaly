## 1. Backend

- [x] 1.1 Add migration: nullable `cover_image_url` column on `businesses`, following the existing `logo_url` pattern; verify it applies cleanly against the running Postgres
- [x] 1.2 Update `Business` entity with `coverImageUrl`
- [x] 1.3 Extend `BusinessService::updateProfile()` to accept and store an uploaded cover image the same way it already handles the logo (separate multipart field, e.g. `cover_image`); verify a unit test covers uploading a cover image and replacing an existing one
- [x] 1.4 Include `cover_image_url` in `CatalogService::publicCatalog()`'s business object (null when unset, never omitted); verify a unit test covers both cases

## 2. Frontend

- [x] 2.1 Add a second `app-image-upload` instance in `business-settings.component` for the cover image, with a wide aspect ratio (e.g. 16:9) via the component's existing `aspectRatio` input; wire it to the profile save alongside the logo
- [x] 2.2 Restructure `catalog-page.component`'s header to the cover-photo-with-overlay pattern: full-width cover image (or a flat orange-palette fallback when unset) with a bottom gradient, the business name overlaid in bold white, and a circular "back to directory" button linking to `/public`
- [x] 2.3 Restyle the existing category chips as rounded pill tabs (filled orange for the active/clicked state, outlined otherwise), keeping their current click-to-scroll behavior unchanged
- [x] 2.4 Verify the header adapts sensibly on desktop widths (shorter/wider band via a media query), not just mobile

## 3. Verification

- [x] 3.1 Run the full backend test suite inside docker-api-1 and the Angular build/tests, confirm both green
- [x] 3.2 Manual smoke test: upload a cover image for the existing "Café Vendaly" business, confirm it appears in `GET /public/catalog/cafe-vendaly` and renders as the header on the public catalog page; confirm a business with no cover image still renders a reasonable fallback header
