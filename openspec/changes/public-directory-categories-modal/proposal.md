# Proposal

## Why

The public directory (`/public`) currently renders every published business as one flat list, with the category/name filter form inline above it, pushing results down and making a category-based browsing experience hard to scan. Grouping by category and moving filters into a modal makes the page easier to browse and keeps the results visible immediately.

## What Changes

- Group the directory's results by category (using the same fixed category list already used for filtering), rendering one section per category with businesses that match, in the order defined by `BUSINESS_CATEGORIES`.
- Businesses with no category set are grouped under an "Otros" (uncategorized) section, shown last.
- When a category filter is active, only that category's section is shown (equivalent to today's filtered flat list, just rendered as one section).
- Replace the inline filter form with a filter button/icon that opens a modal dialog containing the same fields (category select, name search) and the same submit behavior.
- The modal closes on successful submit and on cancel/backdrop/Escape, applying no filter change on cancel.
- No change to the existing directory API, its query params, sorting, or the map/pins behavior — this is a display/interaction change on the existing results.

## Capabilities

### New Capabilities
(none)

### Modified Capabilities
- `business-directory`: adds requirements for category-grouped rendering of listing results and for presenting the filter controls in a modal dialog instead of inline.

## Impact

- `apps/web/src/app/public/directory/public.component.ts` — group `businesses` into per-category sections; add modal open/close state.
- `apps/web/src/app/public/directory/public.component.html` — replace inline `<form>` with a filter trigger + modal; render grouped sections instead of one flat list.
- `apps/web/src/app/public/public.css` — styles for category section headers and the modal.
- No backend/API changes (`BusinessDirectoryApiService`, `CycleBusinessRepository` filtering/sorting stay as-is).
