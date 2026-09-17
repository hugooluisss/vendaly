# Proposal

## Why

The directory currently splits results into one section per category, which reads as many separate lists when there are lots of businesses. A single unified list is easier to scan, and the current 18-per-batch lazy load reveals too much at once for that flatter presentation.

## What Changes

- **Remove category-grouped sections.** The directory renders one unified, ungrouped list of businesses instead of a section per category. The floating category filter chips are unchanged — they still narrow which businesses appear in that single list (no chip active shows all, active chips narrow it).
- **Reduce the lazy-load batch size from 18 to 10.** The list still initially renders the first 10 matching businesses and appends the next 10 on scroll, exactly as before, just with a batch size of 10 instead of 18.
- No change to the API, the category chips' filtering behavior, the name search, or the map (still shows every matching business with coordinates, independent of how many list items are currently revealed).

## Capabilities

### New Capabilities
(none)

### Modified Capabilities
- `business-directory`: removes the category-grouped-sections requirement in favor of one unified list, and changes the incremental-loading batch size from 18 to 10.

## Impact

- `apps/web/src/app/public/directory/public.component.ts` — drop `groupDirectoryBusinesses`/`categoryGroups`/`visibleCategoryGroups` in favor of a single filtered+paginated business array; change the batch size constant from 18 to 10.
- `apps/web/src/app/public/directory/public.component.html` — render one flat list/grid of business cards instead of per-category `<section>`s.
- `apps/web/src/app/public/directory/public.component.spec.ts` — update tests to match the flat-list pagination (drop grouping-specific tests).
- `apps/web/src/app/public/public.css` — remove now-unused category-section-heading styles; keep the card grid and chips styling.
- No backend/API changes.
