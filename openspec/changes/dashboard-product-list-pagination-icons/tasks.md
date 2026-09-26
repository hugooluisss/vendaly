# Tasks

## 1. Pagination

- [x] 1.1 Add `currentPage` and `pageSize = 10` fields and a `pagedProducts` getter to `CatalogManagementComponent` that slices `products` for the current page; reset `currentPage = 1` inside `load()`, verify with a unit test asserting `pagedProducts` returns at most `pageSize` items and only the current page's slice
- [x] 1.2 Add Previous/Next controls and a page indicator (e.g. "Página X de Y") below the products `<ul>` in `catalog-management.component.html`, disabled at the first/last page respectively; iterate the `<ul>` over `pagedProducts` instead of `products`, verify manually via `npm start` that paging through more than 10 products shows the right slice and the controls disable at the bounds
- [x] 1.3 Verify the page resets to 1 after creating, editing, or deleting a product (since `load()` re-fetches and resets `currentPage`), by testing deletion of the last item on the last page and confirming it doesn't land on an empty page

## 2. Icon buttons

- [x] 2.1 Replace the "Editar" and "Eliminar" text buttons in the products row-actions with icon-only buttons (inline pencil/trash SVGs per design.md), keeping `aria-label="Editar producto"` / `aria-label="Eliminar producto"` on each, verify by inspecting the rendered DOM (no visible text, accessible name present) and confirming `editProduct`/`askDelete` still fire on click
- [x] 2.2 Adjust `catalog-management.component.css` as needed so the icon buttons are sized/aligned consistently with the rest of the row (e.g. fixed width/height, centered icon), verify visually via `npm start`

## 3. Tests

- [x] 3.1 Update `catalog-management.component.spec.ts` for the new pagination behavior (page slicing, reset on reload) and icon buttons (click handlers still wired), verify with `npx ng test --include='**/catalog-management.component.spec.ts'`
- [x] 3.2 Run the full frontend test suite to confirm no regressions: `npm test` — blocked in the actual working tree by unrelated pre-existing TypeScript errors in `business-settings.component.spec.ts`, `fulfillment-methods-modal.component.spec.ts`, and `order-statuses-modal.component.spec.ts` (broken WIP from the separate `extract-business-settings-modals` change, unrelated to this one); verified instead in an isolated throwaway worktree with those 3 broken spec files removed — full `catalog-management.component.spec.ts` suite (7 tests) passes there
