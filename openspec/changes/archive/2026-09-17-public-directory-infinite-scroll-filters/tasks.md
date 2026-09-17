# Tasks

## 1. Remove the filter modal

- [x] 1.1 In `public.component.ts`, remove `filterModalOpen`/`draftCategory`/`draftName`/`openFilterModal`/`closeFilterModal`/`submitFilters` and the `ModalComponent` import. Verify by grepping the file for `ModalComponent`/`filterModalOpen` and finding no references.
- [x] 1.2 In `public.component.html`, remove the `<app-modal>` filter dialog and its trigger button. Verify the template no longer references `app-modal` or `filterModalOpen`.

## 2. Floating category filter chips + visible search

- [x] 2.1 In `public.component.ts`, replace the single `category` string with an `activeCategories: Set<string>` (or equivalent) and a `toggleCategory(value: string)` method that adds/removes a category and re-derives the displayed groups; keep `name` as a plain bound field for the always-visible search input.
- [x] 2.2 In `public.component.html`, render one floating chip button per `BUSINESS_CATEGORIES` entry (styled with the existing `.category-chip` pattern), toggling active/inactive state and calling `toggleCategory`; keep the name search as a visible input (not modal-gated) bound to `search()`.
- [x] 2.3 Verify: with no chip active, all categories render (grouped); with one or more chips active, only those categories' sections render; combining an active chip with a name search still narrows correctly.

## 3. Category grouping over the chip-filtered set

- [x] 3.1 Update `groupDirectoryBusinesses` (or its caller) in `public.component.ts` to group the currently chip-filtered subset of `businesses`, not the full unfiltered array, keeping category-list order and the trailing "uncategorized" group. Verify with a unit test covering: no active chips (all groups), one active chip (one group), multiple active chips (multiple groups, in category-list order).

## 4. Incremental loading (infinite scroll)

- [x] 4.1 In `public.component.ts`, add a `visibleCount` field (default 18) and a helper that flattens the chip-filtered, grouped businesses in category order and slices it to `visibleCount` for rendering; reset `visibleCount` to 18 whenever `search()` resolves or the active categories change.
- [x] 4.2 In `public.component.html`, add a sentinel element after the rendered list; in `public.component.ts`, wire an `IntersectionObserver` (created in `ngAfterViewInit`/cleaned up in `ngOnDestroy`) that increments `visibleCount` by 18 (capped at the filtered set's length) when the sentinel becomes visible.
- [x] 4.3 Verify with a unit test: a filtered set of 40 businesses initially reveals 18; simulating the observer callback reveals the next 18, then the remaining 4; a filtered set of 10 reveals all 10 with no further loading triggered.
- [x] 4.4 Verify map markers (`mapMarkers`/`mapBusinesses`) are computed from the full chip/name-filtered set, unaffected by `visibleCount` — confirm by reading the code path, since this must not regress from the current behavior.

## 5. Responsive layout pass

- [x] 5.1 Rework `public.css` directory styles: a wrapping/scrollable floating chips bar (not a single cramped mobile-width row), and a multi-column card grid at the existing 960px breakpoint (and beyond, if needed) for desktop, keeping the existing single/double-column mobile behavior. Remove now-unused modal-trigger/filter-form-specific styles left over from the removed modal flow.
- [x] 5.2 Manually verify in a browser at mobile (~375px), tablet (~768px), and desktop (~1280px+) widths: chips wrap/scroll without overflow, card grid uses available width sensibly, and the map remains usable at each size.

## 6. Regression check

- [x] 6.1 Run the existing Angular unit tests (`apps/web`) and confirm they pass, including the updated/added grouping and pagination specs.
