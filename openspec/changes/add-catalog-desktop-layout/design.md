# Design

## Context

`catalog-page.component.html` renders one flat sequence inside `<main class="catalog-shell">`: `business-header`, `category-nav`, `hours`, then a `@for` loop of `category` sections with `product-card` articles. Layout lives entirely in `apps/web/src/app/public/public.css`, shared with the directory, order-summary, and checkout pages via the same `catalog-shell`/`business-header` class names. The existing breakpoints are `620px` and `960px` (see proposal.md - Why); `960px` is already the "desktop" cutover used for `.catalog-shell` max-width and the directory's 3-column grid, so this change reuses it rather than introducing a new breakpoint.

## Goals / Non-Goals

**Goals:**
- At `≥960px`, arrange the catalog page as two columns: left (header/category-nav/hours), right (product grid), using CSS only.
- Keep the category nav visible without extra scrolling while browsing products, at desktop widths.
- Leave the sub-`960px` markup, classes, and behavior exactly as today.

**Non-Goals:**
- No change to the directory, checkout, or order-summary pages' layouts (they share `public.css` selectors but this change scopes new rules to catalog-page-specific structure).
- No new Angular component, service, or `@Input`/breakpoint-detection logic — this is a CSS Grid/`position: sticky` layout, not a JS-driven responsive component.
- No change to how many categories/products render or how category selection state (`activeCategory`) works — `selectCategory()` and the `#category-<id>` anchor scrolling stay as-is.

## Decisions

- **Two-column CSS Grid on `.catalog-shell`, gated by a `@media (min-width: 960px)` block**, rather than a separate desktop template or Angular `BreakpointObserver`: the existing pattern in `public.css` is pure CSS media queries (see `public.css:11-13`), and the page has no behavior that depends on knowing the viewport size in TypeScript — only visual arrangement changes. Introducing `BreakpointObserver` would add a dependency and state for something CSS already handles.
- **Group left-column content by wrapping `business-header` + `category-nav` + `hours` in a new container element** (e.g. a `<div class="catalog-sidebar">`) in `catalog-page.component.html`, rather than using CSS Grid `order`/`grid-area` on the existing flat siblings: grid placement of three specific elements plus an arbitrary number of `@for`-generated `category` sections is simpler and more robust as "one wrapper vs. the rest" than assigning grid-area to every generated section.
- **`position: sticky` on the category nav within the left column** (not the whole left column) so the business header/hours scroll normally but the category list stays reachable — matches the proposal's "stays reachable while scrolling" scenario without pinning the header/hours, which would waste vertical space on tall business descriptions.
- **Product grid via CSS Grid (`repeat(auto-fill, minmax(...))` or a fixed 2-column grid)** on the right column at `≥960px`, reusing the existing `product-card` markup/classes unchanged — only the *container* around the `@for` loop's output gains a grid display at the new breakpoint.

## Risks / Trade-offs

- **Long business description or many hours rows could push the sticky category nav down awkwardly on short viewports just above 960px** → cap the left column's non-sticky content height or scroll the left column independently if this proves visually cramped during implementation; verify visually rather than guessing a fixed height upfront.
- **Wrapping header/nav/hours in a new container element changes the DOM structure the existing component spec (`catalog-page.component.spec.ts`) may query against** → check that spec's selectors after the template change and update only test queries, not assertions about data/behavior.
- **Shared `public.css` selectors (`.business-header`, `.category-nav`) are also used by other pages** → new desktop rules must be scoped under a catalog-page-specific ancestor selector (e.g. `.catalog-shell:not(.directory) .catalog-sidebar ...`) so the directory/checkout pages, which reuse `.business-header`-like styling incidentally, are not affected.
