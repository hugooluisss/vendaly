# Tasks

## 1. Group results by category

- [x] 1.1 In `public.component.ts`, derive category-grouped sections from `businesses` (one group per `BUSINESS_CATEGORIES` entry with a match, in that order, plus a trailing "uncategorized" group for businesses with no category), recomputed whenever `search()` resolves. Verify by loading `/public` with seeded businesses across at least two categories and one with no category, confirming sections and order.
- [x] 1.2 Update `public.component.html` to render each group as a labeled section (category name as heading) instead of the single flat `<section>`, keeping the existing per-business card markup and routerLink. Verify no section renders for a category with zero matches.
- [x] 1.3 When a category filter is active, verify only that category's section renders (existing filtered API call already returns one category's businesses; grouping degenerates to a single section).

## 2. Move filters into a modal

- [x] 2.1 Add open/close state to `public.component.ts` for the filter modal (e.g. `filterModalOpen = false`) and open/close methods.
- [x] 2.2 In `public.component.html`, replace the inline `<form class="directory-search">` with a filter trigger button (icon/label) that opens the modal, and move the form's `category`/`name` fields inside `<app-modal>` (reusing `apps/web/src/app/shared/modal/modal.component.ts`), keeping the existing `(ngSubmit)="search()"` binding.
- [x] 2.3 Close the modal on successful submit (call the existing close method after `search()` in the submit handler) and on the modal's `(close)` output (cancel/backdrop/Escape), without changing `category`/`name` on cancel. Verify: submitting applies the filter and closes the modal; pressing Escape or clicking the backdrop closes it without altering results.
- [x] 2.4 Style the filter trigger, category section headings, and modal contents in `public.css` (or the modal's own stylesheet) to match the existing directory look. Verify visually in a browser at both desktop and mobile widths.

## 3. Regression check

- [x] 3.1 Run the existing Angular unit tests (`apps/web`) and confirm no directory-related test regresses; update/add a spec for the grouping logic if `public.component.ts` gains a testable method.
