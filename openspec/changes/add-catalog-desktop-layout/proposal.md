# Proposal

## Why

The public catalog page (`apps/web/src/app/public/catalog-page`) only widens its single stacked column as the viewport grows — `.catalog-shell` caps at `960px` and the header/category-nav/hours/product-list sequence stays vertically stacked at every breakpoint (`apps/web/src/app/public/public.css:2,12`). On a desktop screen this leaves large empty side margins and forces a long scroll through every category to browse a menu that could show far more at once, unlike the business directory, which already reflows into a 2–3 column grid past `620px`/`960px` (`public.css:3,11,13`). Customers who reach a catalog link from a desktop browser (not just the QR/mobile flow the current design targets) get a page that reads like an oversized phone screen.

## What Changes

- Give the catalog page a desktop-width layout (from the `960px` breakpoint already used elsewhere in `public.css`): a two-column arrangement with a persistent left column (business header summary, category navigation, hours) and a right column holding the product listing, instead of everything stacked in one column.
- Make the category navigation sticky/persistent in the left column at desktop widths, replacing the horizontal scroll-chip pattern (kept as-is below `960px`) so switching categories doesn't require scrolling back to the top.
- Present products in a multi-column grid within the right column at desktop widths instead of a single stacked list, while keeping the existing mobile single-column product list unchanged.
- Pure template/CSS change to `catalog-page.component.html` and `public.css` (plus, if needed, small structural markup adjustments to group the header/nav/hours into a left-column wrapper) — no changes to `catalog-page.component.ts`, the public catalog API, cart behavior, or the product-options modal.
- The mobile layout shown in the reference screenshots (single column, floating cart bar, horizontal category chips) is unchanged; this only adds a wider-viewport arrangement above the existing `960px` breakpoint.

## Capabilities

### Modified Capabilities
- `public-catalog`: adds a requirement that the public catalog page presents a desktop-appropriate multi-column layout at wide viewports, instead of only widening the existing single-column layout.

## Impact

- **Frontend**: `apps/web/src/app/public/catalog-page/catalog-page.component.html`, `apps/web/src/app/public/public.css`. No changes to `catalog-page.component.ts`, `catalog-api.service`, `cart.service`, or shared components (`modal`).
- **Backend**: none — no API, entity, or migration changes; the public catalog payload shape is unchanged.
- **Other pages**: the business directory (`public/directory`) and order/checkout pages share `public.css` but are out of scope — only catalog-page-specific selectors and the shared `.catalog-shell`/`.business-header`/`.category-nav` rules gain new desktop-only styles scoped so directory/checkout layouts are unaffected.
