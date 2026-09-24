# Tasks

## 1. Template restructuring

- [x] 1.1 In `catalog-page.component.html`, wrap the `business-header`, `category-nav`, and `hours` elements in a new `catalog-sidebar` container, keeping all existing bindings/classes intact, and verify the page still renders identically at the current (sub-960px) width in a browser
- [x] 1.2 Confirm `catalog-page.component.spec.ts` selectors still resolve after the wrapper is added, updating only DOM-query selectors (not assertions) if needed, and verify `npx ng test --include='**/catalog-page.component.spec.ts'` passes

## 2. Desktop layout styles

- [x] 2.1 Add a `@media (min-width: 960px)` block in `public.css` that turns `.catalog-shell` on the catalog page into a two-column CSS Grid (`catalog-sidebar` + product content), scoped so it does not affect `.catalog-shell.directory` or the checkout/order-summary pages
- [x] 2.2 Within that media block, make the `category-nav` `position: sticky` inside `catalog-sidebar` and verify in a browser that it stays visible while scrolling the product listing at ≥960px
- [x] 2.3 Add a grid layout for the product listing container at ≥960px (multi-column `product-card` grid) and verify products render in a multi-column grid instead of a single stacked list at that width

## 3. Verification

- [ ] 3.1 Manually load a published catalog's public page at a mobile width (e.g. 375px) and confirm the layout, category chip scrolling, and floating cart bar are pixel-for-pixel the same as before this change (not verified: browser unavailable in host and Docker)
- [ ] 3.2 Manually load the same catalog at a desktop width (e.g. 1280px) and confirm the two-column layout, sticky category nav, and multi-column product grid all render and that adding a product (including one with options, via the modal) still works and updates the cart bar (not verified: browser unavailable in host and Docker)
- [ ] 3.3 Manually load the business directory and checkout/order-summary pages at both widths to confirm their layouts are unaffected by the new catalog-page-scoped desktop styles (not verified: browser unavailable in host and Docker)
