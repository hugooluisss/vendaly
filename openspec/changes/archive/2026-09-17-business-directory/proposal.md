## Why

Today the only way a customer reaches a business's catalog is a direct link or QR code — there is no way to discover a business on Vendaly itself. The root public route (`/public`) is a static placeholder. This adds a public business directory so customers can browse and search published businesses by category and city/zone, which is also the first step toward Vendaly being useful as a marketplace rather than just a per-business menu link.

## What Changes

- A business gains two new profile fields, set by the owner alongside its existing profile: a **category** (chosen from a fixed list: Restaurante, Café, Salón de belleza, Servicios profesionales, Tienda, Reparaciones, Otro) and a **location** (free-text city/zone, e.g. "Guadalajara", "Roma Norte, CDMX").
- The public root route exposes a directory of published businesses: name, logo, category, location, and a link to their catalog.
- The directory can be filtered by category (exact match against the fixed list) and by location (text match against the location field).
- Existing businesses without a category/location set are still listed (both fields are optional), just not matched by a category/location filter until the owner sets them.

## Capabilities

### New Capabilities
- `business-directory`: public, unauthenticated listing of published businesses with category/location search.

### Modified Capabilities
- `business-management`: business profile configuration gains optional `category` and `location` fields.

## Impact

- **Backend**: `Business` gains nullable `category` (constrained to the fixed list) and `location` (free text) columns; new public endpoint (e.g. `GET /public/businesses?category=&location=`) returning published businesses only; `BusinessController`'s profile update accepts the two new fields.
- **Frontend**: business-settings profile form gains a category picker and a location text field; the `/public` route (currently `PublicComponent`'s placeholder) becomes the directory page with category/location search controls, replacing the placeholder.
- No change to `public-catalog` (per-business catalog page) or `order-intent` — this only adds a way to find a business before landing on its catalog.
