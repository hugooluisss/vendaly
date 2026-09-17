## Why

The public catalog page's current header is a small logo next to the business name — flat compared to what customers expect from a mobile storefront page. The user shared a reference (a mobile loyalty-app screen): a full-width cover photo with the business name overlaid at the bottom, a back button and menu button floating over it, and a rounded pill-style tab bar for navigating sections just below. This adopts that structural pattern for Vendaly's public catalog page, in Vendaly's existing orange/slate palette, mobile-first (the reference is a phone screenshot) with the same structure adapted for desktop.

## What Changes

- A business gains a **cover image** (banner/background photo), distinct from its existing logo — the business owner uploads and crops it separately (reusing the existing client-side image-crop component, with a wide aspect ratio instead of the logo's square one).
- The public catalog page (`catalog-page.component`) header becomes a full-width cover photo with the business name overlaid at the bottom in bold white text over a gradient, and a back-style floating button (returns to the business directory) — replacing the current side-by-side logo+name layout.
- The existing category chips are restyled as a rounded pill tab bar (matching the reference's active-pill look) directly below the cover, keeping their current behavior (scroll-to-category on click) — no new navigation model, just the visual treatment.
- A business without a cover image falls back to a plain colored header (using the existing orange palette) with the logo+name layout it has today — the cover image is optional, not required to publish a catalog.

## Capabilities

### Modified Capabilities
- `business-management`: business profile gains an optional cover image, uploaded/cropped separately from the logo.
- `public-catalog`: the public catalog response includes the cover image URL; the catalog page's header layout changes to the cover-photo pattern.

## Impact

- **Backend**: `Business` gains a nullable `cover_image_url` (mirrors how `logo_url` already works — same S3-compatible upload path, different field); `CatalogService::publicCatalog()` includes it in the business object.
- **Frontend**: `business-settings.component` gains a second `app-image-upload` instance for the cover (wide aspect ratio), `catalog-page.component`'s header markup/CSS is restructured to the cover-photo-with-overlay pattern, category chips restyled as pill tabs. Mobile-first, with the layout adapted (not literally copied) for wider viewports.
- No change to `order-intent`, `business-directory`, or the dashboard's catalog/product management screens beyond the new upload field.
