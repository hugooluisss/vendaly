# Design

## Context

`CatalogManagementComponent` (`apps/web/src/app/dashboard/catalog-management/catalog-management.component.ts`) loads the full `products: Product[]` array for the business in `load()` via `ProductApiService.list()` and renders it in one `@for` loop in the template — see proposal.md for why that's a problem and what's changing. The project has no existing icon library or icon component: `grep`/`find` across `apps/web/src` and `package.json` turn up no SVG icon set, icon font, or Angular Material-style icon module. The public catalog page and other dashboard views also use plain-text buttons, so there's no established icon convention to reuse.

## Goals / Non-Goals

**Goals:**
- Decide how the pencil/trash icons are sourced, since none exist in the codebase yet.
- Keep the pagination state fully local to the component — no new inputs/outputs, no API changes.

**Non-Goals:**
- Not introducing a general-purpose icon system for the rest of the app (dashboard-wide or public-facing) — scoped to this one component's row actions only.
- Not paginating the categories list in the same page, or any other list — only the products `<ul>`.
- Not persisting the current page (e.g. in the URL or storage) — resets to page 1 on every reload, per proposal.md.

## Decisions

- **Icons as inline SVG, not a new dependency.** Alternative considered: add an icon package (e.g. `@heroicons` or similar) as an npm dependency. Rejected for this small, single-component change — pulling in a new package (and dealing with the `web` container's stale-`node_modules` volume rebuild step noted in the top-level `CLAUDE.md`) is disproportionate to needing two icons. Instead, inline a minimal pencil SVG and trash SVG directly in the template (`<svg>` with `currentColor` stroke/fill so they inherit the button's existing text color), matching how `shared/modal`'s close control and similar small UI affordances are already done ad hoc in this codebase rather than through a shared icon component.
- **Accessibility via `aria-label`, not `title`.** Each icon button keeps `type="button"` and gets `aria-label="Editar producto"` / `aria-label="Eliminar producto"` (mirroring the pattern already used for the ingredient-chip remove button's `[attr.aria-label]` in the same template) so removing the visible text doesn't remove the accessible name.
- **Pagination is a plain component field (`currentPage`), not a shared pager component.** Alternative considered: extract a reusable `<app-paginator>` under `shared/`. Rejected for now — this is the only paginated list in the app today, so a shared abstraction would be speculative; a getter (`pagedProducts`) slicing `products` by `currentPage`/`pageSize` (`pageSize = 10`) plus two buttons ("Anterior"/"Siguiente", kept as text since they're layout controls rather than the row actions the proposal is about) is enough. If a second paginated list appears later, extracting a shared component then is a small, low-risk refactor.
- **Page resets to 1 on every `load()`.** `load()` already re-fetches `products` after create/edit/delete; resetting `currentPage = 1` there avoids landing on an out-of-range empty page after the list shrinks (e.g. deleting the last item on the last page).

## Risks / Trade-offs

- [Inline SVGs duplicated if more icons are needed later] → Acceptable now; revisit with a shared icon component only once a third icon shows up elsewhere, per the Decisions section above.
- [Client-side-only pagination means a very large product list is still fetched and held in memory in full] → Acceptable per proposal.md's explicit non-goal of changing the API; matches how `products` is already loaded today, just changes what's rendered at once.
