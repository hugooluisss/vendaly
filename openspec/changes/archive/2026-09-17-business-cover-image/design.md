## Context

The public catalog page (`catalog-page.component`) currently renders a `business-header` with a small square logo beside the name (see `public.css`'s `.business-header`/`.logo` rules). The reference the user shared (a mobile loyalty-app screenshot) uses a full-width cover photo with the name overlaid at the bottom and a pill-style tab bar below it. The category chips already behave like tabs (click to scroll to a category) — this change is a visual restyle of that existing behavior plus a genuinely new field (the cover image), not a new navigation model.

## Goals / Non-Goals

**Goals:**
- Add a cover image, separate from the logo, that the owner uploads/crops (reusing the existing crop component).
- Restyle the catalog page header to a cover-photo-with-overlay pattern, in Vendaly's orange/slate palette, mobile-first with a sensible desktop adaptation.
- Restyle the existing category chips as the reference's rounded pill tabs.

**Non-Goals:**
- No new sections/navigation model (the reference app has Offers/Menu/News/E-shop/Info — Vendaly's catalog page keeps its existing single-page category+product structure; only the visual chrome changes).
- No back/menu floating buttons tied to app-level navigation the reference has (that's a native-app pattern); the public catalog page's floating control is limited to what's meaningful here — a link back to the business directory (`/public`), since that's the only "back" destination that exists.

## Decisions

- **`cover_image_url` as a separate nullable column on `Business`**, following exactly the same pattern as `logo_url` (S3-compatible upload, `ProductImage`-style replace-on-upload semantics reused at the business level) — no new table needed, this is one more optional image field on an entity that already has one.
- **Reuse the existing `ImageUploadComponent`/crop flow** for the cover, with a wide aspect ratio (e.g. 16:9) passed via its existing `aspectRatio` input, instead of building a second upload/crop implementation — that component was explicitly designed to be reusable across fields.
- **Header markup becomes**: cover photo (or a flat orange-palette fallback background when unset) as a full-bleed block, a semi-transparent gradient at its bottom for text legibility, the business name overlaid in bold white, and a circular floating "back to directory" button top-left — directly adapted from the reference's structure, dropping the elements that don't apply (menu button, wallet buttons, bottom app nav).
- **Category chips restyled, not restructured**: same `@for` loop and click-to-scroll behavior already in `catalog-page.component.html`, just updated CSS to the pill/tab look (rounded, active state filled with the orange primary, inactive state outlined) matching the reference's tab bar, since the existing chips already are the tab bar conceptually.
- **Mobile-first, adapted for desktop**: the cover photo keeps a fixed aspect ratio on narrow viewports (matching the reference) and becomes a shorter, wider band on desktop via a media query — same mobile-first media-query pattern already used throughout `public.css`.

## Risks / Trade-offs

- **Large cover images could slow the public catalog's first paint** on mobile connections → mitigated by the existing crop component's `resizeToWidth` already used for the logo (per `image-cropper`'s implementation) — apply the same resize ceiling to the cover upload, just at a size appropriate for a banner rather than a square logo.
- **A business with a long name could overflow the overlay text** on very narrow screens → handled with standard text truncation/wrapping in CSS, not a functional concern, consistent with how the rest of the app already handles variable-length business names.
