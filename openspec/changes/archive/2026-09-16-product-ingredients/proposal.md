## Why

Several business types on Vendaly (restaurants, cafés) sell products whose composition matters to the customer even when it isn't a purchasable option (e.g. "lleva jitomate, cebolla, queso"). Business owners currently have no way to list this on a product, and customers browsing the public catalog have no visibility into it. This adds an optional, simple ingredient list per product — informational only, not priced or selectable, and distinct from the future `ProductOption`/`ProductOptionValue` variant system already reserved in the domain model.

## What Changes

- A product can have zero or more named ingredients, managed by the business owner alongside the product's other fields (name, description, price, image).
- Ingredients are purely descriptive: no price, no per-order selection, no effect on order totals or the WhatsApp message.
- The public catalog displays a product's ingredients (when any exist) as part of its listing.
- Ingredients respect the same soft-delete lifecycle as their parent product (already implemented for `Category`/`Product`): deleting a product's ingredient list happens together with editing the product, not as a separate customer-facing action.

## Capabilities

### Modified Capabilities
- `catalog-management`: adds ingredient management (add/remove/reorder) to product create/edit.
- `public-catalog`: the public catalog response includes each product's ingredient list.

## Impact

- **Backend**: new `product_ingredients` table (id, product_id, name, position) + migration, `Product` gains an ingredients association, `ProductService` gains ingredient add/replace logic, `CatalogService::publicCatalog()` includes ingredients per product.
- **Frontend**: product modal (apps/web dashboard catalog-management) gains an ingredients input (add/remove chips), public catalog product card shows ingredients when present.
- No change to `order-intent`: ingredients are never added to an order or the WhatsApp message.
