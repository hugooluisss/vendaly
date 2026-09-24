# Proposal

## Why

A business's public catalog page currently gives customers no way to reach the business anywhere except via the order flow (WhatsApp deep link at checkout) — there is no link to its Facebook page, Instagram profile, or website, and the WhatsApp number itself (already required on every business) isn't surfaced as a contact channel on the catalog page at all. Businesses commonly drive traffic between their social presence and their Vendaly catalog; without visible links in the other direction, that traffic is one-way.

## What Changes

- Add three new optional profile fields to a business: `facebook_url`, `instagram_url`, `website_url` — each a full URL, validated as well-formed when present, following the same optional-field pattern already used for `description`/`category`/`location` in `BusinessService::updateProfile`.
- Add these three fields to the business-settings dashboard form (`apps/web/src/app/dashboard/business-settings`), next to the existing WhatsApp field, using plain URL inputs (no new validation library).
- On the public catalog page, render a small set of contact icon links in the business header: Facebook, Instagram, WhatsApp, and Website. WhatsApp is **not** a new field — it reuses the business's existing, already-required `whatsapp_number` to build a `https://wa.me/<number>` link.
- Each of the four icons is independently optional at render time: if the underlying value (facebook_url, instagram_url, whatsapp_number, or website_url) is unset, that icon is omitted entirely — never shown disabled or empty.
- Icons are plain inline SVGs styled as a small circular badge (matching the existing `.directory-back` badge look), positioned in the header opposite the back button, per the reference layout.
- No new icon library or shared icon component is introduced — this follows the existing pattern of no icon dependencies in the frontend.

## Capabilities

### Modified Capabilities
- `business-management`: the "Business profile configuration" requirement is extended to include Facebook, Instagram, and Website URLs as additional settable, optional profile fields, each validated as a well-formed URL when present.
- `public-catalog`: adds a requirement that the public catalog lookup response exposes the business's WhatsApp number and social/website URLs, and that the catalog page renders a contact icon per channel that has a value, omitting any channel that doesn't.

## Impact

- **Backend**: `apps/api/src/Domain/Entity/Business.php` (3 new nullable string columns), a new Cycle migration under `apps/api/migrations/`, `BusinessService::updateProfile()` (validate + persist the 3 new fields), `PublicEntityMapper::business()` (expose them to the dashboard), `CatalogService::publicCatalog()` (expose `whatsapp_number` plus the 3 new fields in the public `business` sub-object — `whatsapp_number` is not currently exposed there at all).
- **Frontend**: `apps/web/src/app/dashboard/business.models.ts` (`Business` interface), `apps/web/src/app/dashboard/business-settings/business-settings.component.ts` + `.html` (3 new form fields, patch/save), `apps/web/src/app/public/public.models.ts` (`PublicCatalog.business` shape), `apps/web/src/app/public/catalog-page/catalog-page.component.html` + `apps/web/src/app/public/public.css` (conditional icon rendering + styling).
- **No breaking changes**: all three new fields are optional and nullable; existing businesses without them are unaffected, and the public page's existing layout/behavior for businesses with none of these values set is unchanged.
