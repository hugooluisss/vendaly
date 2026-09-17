# Proposal

## Why

The just-shipped directory modal feels mobile-only and adds an extra click to filter. Browsing also loads and renders every published business at once, which won't scale and reads as a wall of cards. Replacing the modal with always-visible floating category filters, paginating the list with infinite scroll, and reworking the layout for desktop fixes all three.

## What Changes

- **Remove the filter modal** added in `public-directory-categories-modal`: no more `app-modal` dialog for filtering.
- **Floating category filter chips**: category options render as toggleable floating buttons above the results (reusing the existing `.category-chip` visual pattern). Multiple categories can be active at once. With no chip active, every category is shown; with one or more active, only businesses in an active category are shown. The name search field stays as a simple visible input (not modal-gated).
- **Infinite scroll / lazy loading**: render the first 18 businesses (of the current filtered set) on load; when the visitor scrolls to the end of the rendered list, append the next 18 until all matching businesses are shown. No backend pagination — the existing `/public/businesses` endpoint already returns the full filtered set in one call; the frontend reveals it incrementally.
- **Map keeps showing every matching business as a pin**, independent of how many cards are currently revealed by lazy loading (unchanged from current behavior — pins are already derived from the full filtered result, not the rendered page).
- **Responsive redesign**: rework the directory layout so it reads well on desktop (wider multi-column grid, chips bar that doesn't feel like a phone-only strip) as well as mobile, using the project's existing CSS variables and breakpoint conventions.
- No change to the existing directory API, its query params, or sorting/filtering semantics server-side.

## Capabilities

### New Capabilities
(none)

### Modified Capabilities
- `business-directory`: replaces the "Filter controls presented in a modal" requirement with always-visible category filter chips and a visible name search input; adds an incremental-loading requirement for the results list.

## Impact

- `apps/web/src/app/public/directory/public.component.ts` — replace modal open/close state with active-categories set (multi-select) and visible-count-based pagination state; add scroll-triggered load-more.
- `apps/web/src/app/public/directory/public.component.html` — remove `<app-modal>` filter form; add floating category chip buttons and a visible search input; render only the currently revealed slice of each category group; add a load-more sentinel/trigger.
- `apps/web/src/app/public/directory/public.component.spec.ts` — update/extend grouping and pagination logic tests.
- `apps/web/src/app/public/public.css` — floating chips bar styling, responsive multi-column grid for desktop, remove now-unused modal-trigger/filter-form styles specific to the removed modal flow.
- No backend/API changes.
