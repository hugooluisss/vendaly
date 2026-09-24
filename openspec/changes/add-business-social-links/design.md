# Design

## Context

`Business` (apps/api/src/Domain/Entity/Business.php) has `whatsappNumber` (`whatsapp_number`, required at the product level though the column is nullable) but no social/website fields. `BusinessService::updateProfile()` validates and assigns each optional profile field individually via `array_key_exists($field, $input)` checks (see `whatsapp_number`, `description`, `category`, `location`), and `PublicEntityMapper::business()` is the single place the owner-facing (dashboard `me`/`update`) response shape is built. Separately, `CatalogService::publicCatalog()` builds a much smaller, hand-picked `business` sub-object for the public, unauthenticated `GET /public/catalog/:slug` response — it does not reuse `PublicEntityMapper`, and today omits `whatsapp_number` entirely even though the field already exists on the entity. See proposal.md for motivation.

## Goals / Non-Goals

**Goals:**
- Add `facebook_url`, `instagram_url`, `website_url` as nullable columns on `businesses`, settable/clearable through the same owner-update path as other optional profile fields.
- Expose all four contact channels (the three new URLs plus the existing `whatsapp_number`) in the public catalog response.
- Render a small, icon-only contact row in the catalog page header, each icon present only when its value is set.

**Non-Goals:**
- No new "social links" sub-resource, ordering/positions, or additional platforms beyond Facebook/Instagram/Website/WhatsApp.
- No icon library or shared icon component — inline SVGs only, matching the project's existing lack of an icon dependency.
- No change to the WhatsApp *ordering* flow (`wa.me` link built at checkout) — this is a separate, purely display-oriented WhatsApp link on the catalog page, reusing the same underlying number.
- No directory-listing changes — `GET /public/businesses` and `DirectoryBusiness` are untouched; these fields are catalog-page-only for this change.

## Decisions

- **Three separate nullable `VARCHAR` columns (`facebook_url`, `instagram_url`, `website_url`)** rather than a single JSON "social links" column: matches the existing flat, one-column-per-field style of `Business` (see `logo_url`, `cover_image_url`, `whatsapp_number`), keeps validation per-field and simple, and avoids introducing a new serialization/deserialization concern for a fixed, small set of channels.
- **URL validation via `filter_var($url, FILTER_VALIDATE_URL)`** in `BusinessService::updateProfile()`, following the same `array_key_exists` + trim + null-if-empty + throw-`DomainException`-on-invalid pattern already used for `whatsapp_number` — no new validator class needed for three fields with an identical shape.
- **`CatalogService::publicCatalog()`'s hand-built `business` array is extended directly** (adding `whatsapp_number`, `facebook_url`, `instagram_url`, `website_url`) rather than switched to reuse `PublicEntityMapper::business()`: the mapper returns owner-only fields (`is_published`, fees, payment methods, order statuses) that must never appear in the public, unauthenticated response — reusing it would require stripping fields back out, which is more error-prone than adding four keys to the existing explicit array.
- **Icons as inline SVG, styled as a circular badge matching `.directory-back`**, positioned at the opposite (top-right) corner of the business header: no new dependency, and it visually mirrors the reference layout (back button top-left, contact icons top-right) already documented in the desktop-layout change's header structure.
- **Icon visibility driven by simple template conditionals** (`@if (catalog.business.facebook_url) { ... }` etc.) rather than building an array of "channels" to iterate in the component class — four fixed, independently-optional fields don't need a data-driven abstraction, and this matches the template's existing declarative style (see `@if (catalog.business.cover_image_url)` for the cover image).

## Risks / Trade-offs

- **`whatsapp_number` was never public before** → now that it's exposed via the public catalog response, confirm no existing consumer relies on its absence there (checked: nothing in the frontend currently reads `catalog.business.whatsapp_number`, since the checkout flow gets its own server-built `whatsapp_url` from a different endpoint) — this is a pure addition, not a behavior change to an existing field.
- **Four new absolute-positioned icons in a fixed-height header could crowd the existing back button and business name on narrow phone widths** → cap the icon row to a compact size and verify visually at the same mobile widths already used for the desktop-layout change, adjusting spacing rather than assuming it fits.
- **`filter_var(..., FILTER_VALIDATE_URL)` accepts some unusual but technically valid URLs (e.g. non-http schemes)** → acceptable for this change; a stricter allow-list (`http`/`https` only) can be added later if it becomes a problem, but is not required by the current requirement.
