# Proposal

## Why

Businesses on Vendaly sell products that commonly need customer-facing choices — a coffee has an interchangeable sweetener (brown sugar, refined sugar, Splenda), or an optional add-on (whipped cream), sometimes with a price difference. Today `Product` only supports a flat, informational ingredient list (`ProductIngredient`) with no selection semantics or price impact, so businesses can't model these choices and customers can't express them before the order is sent to WhatsApp. `catalog-management`'s existing "Product model extensibility for future options" requirement already anticipated `ProductOption`/`ProductOptionValue` for this; this change implements it.

## What Changes

- Add `ProductOption` (a named choice group on a product, e.g. "Sugar type") with a `selection_type` of `single` (radio, exactly one) or `multiple` (checkboxes, zero or more) and a `required` flag (single-select groups may be required; multi-select groups are always optional-per-value).
- Add `ProductOptionValue` (a choice within a group, e.g. "Brown sugar", "Splenda", "Whipped cream") with a `price_delta` (signed, defaults to 0) added to the item's unit price when selected.
- Business owner CRUD for option groups and values in dashboard catalog management, scoped to their own products (create/edit/reorder/delete groups and values).
- Public catalog response includes each active product's option groups/values so the customer can choose before adding to their selection.
- Customer selection (cart) captures the chosen option values per line item; price calculation includes selected `price_delta`s.
- Order persistence (`POST /public/orders`) stores selected option values per `OrderItem`, validated against `required`/`single`/`multiple` constraints server-side.
- WhatsApp message generation lists each item's selected options (with any price delta) alongside its existing name/quantity/note/price.

## Capabilities

### New Capabilities
- `product-options`: business-owner configuration of option groups/values on a product (single-select vs multi-select, optional price deltas, required/optional single-select groups).

### Modified Capabilities
- `catalog-management`: the "Product model extensibility for future options" requirement is replaced by an implemented requirement describing actual option-group attachment to a product (superseded by `product-options`, but the product-side attachment scenario belongs here since it's part of product configuration).
- `public-catalog`: the public catalog lookup response must include each active product's option groups/values (not just its ingredient list).
- `order-intent`: cart/selection management must capture per-item selected option values and their price impact; order persistence must validate and store selections per `OrderItem`; WhatsApp message generation must include selected options in the formatted item lines.

## Impact

- **Backend**: new `ProductOption`/`ProductOptionValue` entities + migration, new repository/service methods in `ProductService`/`CatalogService`, `OrderItem` gains a selected-options relation, `OrderService` gains server-side validation of selections against group constraints, `Web/Public` catalog and order controllers/serializers updated.
- **Frontend**: `dashboard/catalog-management` gains option-group editing UI; `catalog.models.ts` / `product-api.service.ts` extended; `public/catalog-page` gains selection UI per product; `public.models.ts`, `cart.service.ts`, order payload building, and WhatsApp message formatting updated to carry selected options.
- **No breaking changes** to existing products without options (empty option list is valid, mirrors existing ingredient-list pattern).
