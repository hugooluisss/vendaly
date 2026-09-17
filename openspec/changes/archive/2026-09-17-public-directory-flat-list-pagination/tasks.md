# Tasks

## 1. Flatten the list

- [x] 1.1 In `public.component.ts`, remove `groupDirectoryBusinesses`, `DirectoryBusinessGroup`, `categoryGroups`, and `visibleCategoryGroups`; replace with a single `filteredBusinesses` (chip/name-filtered, unsorted beyond whatever order the API already returns) and a `visibleBusinesses` getter that slices it to the current `visibleCount`. Verify by grepping the file for `categoryGroups`/`groupDirectoryBusinesses` and finding no references.
- [x] 1.2 In `public.component.html`, replace the per-category `<section>` loop with a single list/grid rendering `visibleBusinesses` directly (keep the existing card markup, logo/placeholder, and `routerLink`). Verify no `directory-section`/`directory-section-heading` markup remains.
- [x] 1.3 Update `public.component.spec.ts`: remove/replace the `groupDirectoryBusinesses` tests with tests for the flat filtered+paginated list (chip filtering narrows the flat list; combining chips with name search narrows further).

## 2. Reduce batch size to 10

- [x] 2.1 In `public.component.ts`, change the pagination batch size from 18 to 10 (initial `visibleCount` and the increment in `loadMore()`). Verify with a unit test: a filtered set of 25 businesses initially reveals 10, then 20, then 25 after two load-more triggers.
- [x] 2.2 Confirm `visibleCount` still resets to 10 (not 18) whenever `search()` resolves or the active categories change.

## 3. Cleanup and regression check

- [x] 3.1 Remove now-unused `.directory-sections`/`.directory-section`/`.directory-section-heading` styles from `public.css`; keep `.directory-section-list` (or rename it) as the flat grid's styling, adjusting selectors in the template to match.
- [x] 3.2 Run the existing Angular unit tests (`apps/web`) and confirm they pass, including the updated flat-list/pagination specs.
