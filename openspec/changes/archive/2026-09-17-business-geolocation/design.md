## Context

`Business` already has `category`/`location` (free text) from `business-directory`. That change's design explicitly deferred geolocation as a non-goal — this change is the deferred follow-up, confirmed with the user: internal haversine distance (no external distance API), Leaflet + OpenStreetMap for map rendering (no API key), pin-based coordinate capture for the owner, and graceful fallback to text search when the visitor declines location permission.

## Goals / Non-Goals

**Goals:**
- Let a business owner pin their exact location on a map.
- Let a visitor see businesses on a map and get a nearest-first ordering when they share their position.
- Zero paid/keyed external services anywhere in the flow.

**Non-Goals:**
- No geocoding of the free-text `location` field into coordinates automatically (e.g. turning "Roma Norte, CDMX" into lat/lng via a geocoding API) — the owner sets coordinates by pinning, `location` stays a separate human-readable label the owner types themselves.
- No routing/travel-time ("15 min away") — straight-line (haversine) distance only, as confirmed.
- No server-side IP-based geolocation fallback — if the browser's `navigator.geolocation` is denied or unavailable, the experience simply falls back to the existing text/category search, nothing more elaborate.

## Decisions

- **Plain `latitude`/`longitude` DECIMAL columns on `Business`**, not a PostGIS geography column or a separate `BusinessLocation` table. Rationale: haversine distance over a business's-worth of rows (hundreds to low thousands for an MVP marketplace) needs no spatial index or extension — a plain SQL haversine expression in the `ORDER BY`/`WHERE` is sufficient and keeps the stack dependency-free (matches the project's existing preference for the simplest thing that satisfies the spec, e.g. `location`'s `ILIKE` substring match over introducing full-text search).
- **Haversine computed in SQL** (not fetched-then-sorted in PHP): `BusinessRepository::findPublishedDirectory()` adds the haversine expression as a computed column and orders by it when a visitor position is given, so the database does the filtering/sorting instead of loading every published business into PHP to sort in memory — same "let the database do set-based work" principle already applied to `OrderRepository`'s date-range filtering.
- **Leaflet + OpenStreetMap** for both the owner's pin-picker map and the public directory's pin map, via `leaflet` (npm) and its default OSM tile layer — free, no API key, works offline of any Vendaly-side config. One shared Angular map component wraps Leaflet so both screens configure it (draggable single pin vs. multiple read-only pins) rather than each screen embedding Leaflet directly.
- **`navigator.geolocation.getCurrentPosition()`** directly in the public directory component for the visitor's position — no third-party geolocation SDK; it's a browser-native API. On denial/error/unsupported browser, the component simply doesn't send a position to the directory endpoint, which per the spec falls back to the existing search.
- **Coordinates are a pair, atomic**: validated and stored together (never latitude without longitude) — matches the spec's explicit scenario and avoids a half-set location that would break the haversine math.

## Risks / Trade-offs

- **Haversine in SQL doesn't scale to a huge catalog of businesses** (it's O(n) per query, no spatial index) → acceptable for MVP marketplace scale; if the business directory grows into the tens of thousands of rows, revisit with PostGIS/a spatial index then — not blocked by this design since the column is plain lat/lng, not something PostGIS-specific that would need re-migrating.
- **Owner must manually place a pin** (no address-to-coordinate autofill) → confirmed acceptable trade-off (non-goal), but is genuine added friction versus typing an address; mitigated by defaulting the map's initial view to the browser's current position (if the owner allows it) so dragging a pin from a sensible starting point is quick, not a blind global map.
- **Free OpenStreetMap tile servers have fair-use rate limits** (no SLA, no key) → acceptable for MVP traffic; if usage grows, self-hosting tiles or moving to a paid tile provider is a swap of the tile URL in one shared map component, not a rearchitecture.
