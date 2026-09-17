## Context

`Business` already has `is_published`/`slug`/`logo_url`/`description`. The public root route `/public` currently renders `PublicComponent`'s static placeholder (`<h1>Vendaly</h1><p>Public catalog</p>`) — this change replaces that placeholder's purpose, not `public-catalog`'s per-business page.

## Goals / Non-Goals

**Goals:**
- Let a business owner tag their business with a category (fixed list) and a location (free text).
- Let a visitor browse/search published businesses by those two fields.

**Non-Goals:**
- No geolocation, maps, or distance-based search (explicitly deferred — confirmed with the user; location is a text field, matched by substring).
- No pagination/infinite scroll polish beyond a simple bounded list — first cut, same posture as `order-history`.
- No category management UI (the list is fixed in code, not editable by business owners or admins).

## Decisions

- **Category as a backend-enforced fixed list**, not a free-text field or a separate `categories` table. Rationale: the spec requires reliable exact-match filtering; a fixed list is validated in `BusinessService` the same way `whatsapp_number` format is already validated, no new table/relation needed for 7 static values. Values: `restaurant`, `cafe`, `beauty_salon`, `professional_services`, `store`, `repair_services`, `other` (stored as the English key; the frontend renders the Spanish label — same pattern already used for status/enum-like fields, keeps backend/frontend decoupled from a hardcoded translated string as the source of truth).
- **Location as a plain nullable `VARCHAR` column on `Business`**, matched with `ILIKE '%term%'` (Postgres case-insensitive substring) for filtering — simplest option that satisfies "search by location" without geocoding, per the confirmed non-goal.
- **New `GET /public/businesses` endpoint**, separate from `GET /public/catalog/:slug` — this is a directory query (many businesses, thin data), while the catalog endpoint is a single-business deep read (categories, products, hours). Conflating them into one endpoint would force one shape to awkwardly serve two very different call patterns.
- **Reuse `BusinessRepository`** with a new `findPublishedDirectory(?category, ?location)` method rather than a separate repository — this is still a `Business`-scoped query, no new aggregate root.

## Risks / Trade-offs

- **Substring location matching is imprecise** (e.g. "Roma Norte" won't match a search for "CDMX" even though it's in Mexico City) → acceptable for MVP per the confirmed scope; upgrading to structured city/state fields or geocoding is a clearly separable future change, not blocked by this design (the column is just text, nothing else depends on its exact format).
- **Fixed category list requires a code change to extend** (new business type support means adding an enum value + a migration/no migration depending on how it's stored) → acceptable trade-off for reliable filtering now; revisit if the list needs frequent extension.
