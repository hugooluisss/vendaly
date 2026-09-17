# Design

## Context

`apps/web/src/app/public/directory/public.component.ts` currently fetches the full filtered business list from `BusinessDirectoryApiService.get()` (single `category` query param) in one call, groups it client-side by category (`groupDirectoryBusinesses`), and renders everything at once inside a filter modal (`ModalComponent`). See `proposal.md` for why this is changing. The `/public/businesses` endpoint has no pagination support (no `page`/`limit`/`offset` params) and is not being changed by this work.

## Goals / Non-Goals

**Goals:**
- Multi-select category filtering without a backend change.
- Reveal results 18 at a time, appending more on scroll, without a backend change.
- Keep map pins representing the full filtered set regardless of how many list cards are revealed.
- A layout that reads as a real desktop page, not a stretched phone view.

**Non-Goals:**
- Backend pagination or a multi-category query param — out of scope; revisit only if the business count grows large enough that fetching the full filtered set becomes a real payload/latency problem.
- Virtual scrolling / windowing (DOM recycling) — 18-at-a-time appending is enough at current and expected near-term business counts.

## Decisions

- **Client-side category filtering, single API call.** The directory still calls `BusinessDirectoryApiService.get()` without a `category` param (only `name`/position), fetching the full name-filtered set once. Active category chips filter that already-fetched array in the component, and `groupDirectoryBusinesses` runs against the chip-filtered subset. Alternative considered: issue one API call per active category and merge — rejected, adds request fan-out and merge/de-dup complexity for no user-visible benefit when the whole list is already one small payload.
- **Client-side incremental reveal, not server pagination.** A `visibleCount` (starting at 18, incrementing by 18) slices the category-ordered, chip-filtered, flattened business list for rendering. `IntersectionObserver` on a sentinel element at the end of the rendered list bumps `visibleCount` when it enters the viewport. Alternative considered: server-side `page`/`limit` params — rejected for this pass since it would require an API change and the endpoint already returns the full set cheaply; revisit if catalogs grow large (see Non-Goals).
- **Map markers stay derived from the full chip/name-filtered set, not the revealed slice.** `mapBusinesses`/`mapMarkers` continue to be computed from all matching businesses with coordinates (as today), independent of `visibleCount`, so scrolling the list never removes or adds pins.
- **Reuse the existing `.category-chip` visual pattern** (already used for category navigation on the business catalog page) for the floating directory filter chips, for visual consistency instead of inventing a new chip style.
- **Responsive layout**: extend the existing breakpoint convention (620px / 960px media queries already in `public.css`) with a multi-column card grid at the 960px breakpoint and a chips bar that wraps/scrolls rather than a single cramped row, instead of introducing a new CSS framework or breakpoint system.

## Risks / Trade-offs

- Fetching the entire filtered set in one call scales worse than server pagination as the number of published businesses grows → acceptable now; flagged in Non-Goals as the trigger to revisit.
- `IntersectionObserver` sentinel-based loading can double-trigger on fast scroll or resize if not guarded → guard incrementing `visibleCount` with an "already loading this batch" check (synchronous since it's just an array slice, but keep the guard for clarity and to avoid redundant observer callbacks).
