# Proposal

## Why

The dashboard catalog-management product list (`apps/web/src/app/dashboard/catalog-management`) renders every product for the business in one unbounded `<ul>`, with no way to page through a large catalog, and each row's edit/delete actions are plain text buttons ("Editar" / "Eliminar") that take up more visual space than icon buttons would and read as less polished next to the rest of the Tailwind-based dashboard.

## What Changes

- Add client-side pagination to the products list in `catalog-management.component.ts`/`.html`: show a fixed page size (10 products per page) with Previous/Next controls and a page indicator, resetting to page 1 whenever the underlying `products` list is reloaded (create/edit/delete/category change).
- Replace the text labels on the product row's "Editar" and "Eliminar" action buttons with icon-only buttons (pencil / trash icons, consistent with any icon usage already established in `shared/`), keeping an `aria-label` on each button for accessibility since the visible text is removed.
- No change to how many products are fetched from the API — `ProductApiService.list()` already returns the full list for the business; pagination is purely a display concern, matching the "no backend/API changes" scope of this change.

## Capabilities

### New Capabilities
(none)

### Modified Capabilities
(none — this is a presentation-only change to the dashboard catalog-management view; it does not alter any requirement in `catalog-management`'s spec, which does not describe list pagination or the visual form of the edit/delete controls)

## Impact

- **Frontend (`apps/web`) only**: `apps/web/src/app/dashboard/catalog-management/catalog-management.component.ts`, `.component.html`, `.component.css`, and its spec file. No backend/API changes.
