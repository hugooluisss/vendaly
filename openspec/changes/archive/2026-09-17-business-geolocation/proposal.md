## Why

`business-directory`'s location field is free text, explicitly scoped to substring matching with geolocation deferred. Text search can't answer "what's near me", which is what a customer actually wants when discovering a business on Vendaly. This adds real coordinates, an internally-computed nearest-first search, and map display (business owner pinning their location, and customers seeing results on a map) — while deliberately not depending on any paid mapping/geocoding API.

## What Changes

- A business gains a coordinate pair (latitude/longitude), set by the owner by placing a pin on an interactive map in their profile — the existing free-text `location` field is kept as a human-readable label shown alongside the pin, not replaced by it.
- The public directory can be sorted/filtered by distance from the visitor's current position (browser geolocation), computed by the backend using the haversine formula against stored coordinates — no external distance/routing API call.
- The public directory shows business pins on an interactive map (Leaflet + OpenStreetMap tiles — free, no API key) alongside the existing list.
- If the visitor declines the location permission, the directory falls back to the existing category/text-location search from `business-directory` — proximity search is additive, not a replacement.
- A business without coordinates set is still listed and searchable by category/text-location, just never appears in a "nearest to me" sort.

## Capabilities

### Modified Capabilities
- `business-management`: business profile gains optional latitude/longitude, set via a map pin.
- `business-directory`: directory gains distance-based sorting and map display; text-location search is unchanged and remains the fallback.

## Impact

- **Backend**: `Business` gains nullable `latitude`/`longitude` (decimal) columns; `BusinessRepository::findPublishedDirectory()` gains an optional `(lat, lng)` visitor position parameter that, when given, computes distance server-side (haversine, no PostGIS/extension required at this scale) and sorts by it; response includes each business's coordinates (or null) so the frontend can plot pins.
- **Frontend**: `business-settings.component` gains a Leaflet map for pinning the business location; `public.component` (directory) gains a Leaflet map showing published businesses' pins and requests the visitor's position via `navigator.geolocation`, falling back to the existing search UI when denied or unavailable.
- No paid/keyed API anywhere in this change: Leaflet + OpenStreetMap tile servers for rendering, haversine math (plain SQL/PHP) for "nearest", browser-native `navigator.geolocation` for the visitor's position.
