# Tasks

## 1. Backend: data model

- [x] 1.1 Add a new Cycle migration `apps/api/migrations/20260924.000000_16_business_social_links.php` (following `20260916.000007_08_business_cover_image.php`'s pattern) that adds nullable `facebook_url`, `instagram_url`, `website_url` VARCHAR columns to `businesses` in `up()` and drops them in `down()`, and verify `docker exec vendaly-api-1 php bin/migrate.php` runs it cleanly
- [x] 1.2 Add `facebookUrl` (`facebook_url`), `instagramUrl` (`instagram_url`), `websiteUrl` (`website_url`) nullable string `#[Column]` properties to `apps/api/src/Domain/Entity/Business.php`, matching the existing `whatsappNumber` property style

## 2. Backend: profile update and exposure

- [x] 2.1 In `BusinessService::updateProfile()`, add `array_key_exists` handling for `facebook_url`, `instagram_url`, `website_url`: trim, null-if-empty, validate non-empty values with `filter_var($url, FILTER_VALIDATE_URL)` and throw `DomainException` (identifying the field) when invalid, otherwise assign to the corresponding entity property
- [x] 2.2 Add `facebook_url`, `instagram_url`, `website_url` to the array returned by `PublicEntityMapper::business()` so the dashboard `me`/`update` responses include them
- [x] 2.3 Add `whatsapp_number`, `facebook_url`, `instagram_url`, `website_url` to the `business` sub-array built in `CatalogService::publicCatalog()`
- [x] 2.4 Add/extend Codeception Unit tests covering: successful update of the three new fields, rejection of a malformed URL for each, clearing a previously set field, and the public catalog response including all four contact fields (set and null cases) — verify with `docker exec vendaly-api-1 vendor/bin/codecept run Unit`

## 3. Frontend: models and dashboard form

- [x] 3.1 Add `facebook_url?: string | null`, `instagram_url?: string | null`, `website_url?: string | null` to the `Business` interface in `apps/web/src/app/dashboard/business.models.ts`
- [x] 3.2 Add `whatsapp_number`, `facebook_url`, `instagram_url`, `website_url` (all `string | null`, optional) to the `PublicCatalog.business` inline type in `apps/web/src/app/public/public.models.ts`
- [x] 3.3 Add three new `FormControl`s (`facebook_url`, `instagram_url`, `website_url`, all optional, no `Validators.required`) to the `profile` FormGroup in `business-settings.component.ts`, patch them from `result.business` in the constructor's subscribe callback alongside the existing fields, and verify they're included in the payload `saveProfile()` sends via `this.profile.getRawValue()`
- [x] 3.4 Add three labeled URL inputs (`type="url"`) for Facebook, Instagram, and Website to `business-settings.component.html`, next to the existing WhatsApp field, following its exact markup pattern
- [ ] 3.5 Manually verify in the dashboard: saving with all three fields empty succeeds, saving with a valid URL in each persists and reloads correctly, and saving an invalid URL surfaces the backend's validation error via the existing notification service (not verified: browser/dashboard session unavailable; build and backend unit coverage passed)

## 4. Frontend: public catalog page icons

- [x] 4.1 In `catalog-page.component.html`'s business-header block, add a contact-icon container positioned opposite `.directory-back` (top-right), with one `@if` per channel (`catalog.business.facebook_url`, `catalog.business.instagram_url`, `catalog.business.whatsapp_number`, `catalog.business.website_url`) rendering an `<a>` with an inline SVG icon; the WhatsApp link is built as `https://wa.me/<digits-only whatsapp_number>` (reuse or mirror however the existing checkout flow strips the number, if applicable) and the others link directly to their stored URL
- [x] 4.2 Add styling in `public.css` for the new icon container/badges, matching `.directory-back`'s circular-badge look, and verify it doesn't overlap the business name/back button at mobile widths (≤400px) and at the desktop two-column layout (≥960px)
- [ ] 4.3 Manually verify: a business with all four values set shows all four icons linking correctly; a business with none set shows none (no placeholders); a business with a partial set (e.g. only website_url) shows exactly that one icon (not verified: browser/catalog session unavailable)
