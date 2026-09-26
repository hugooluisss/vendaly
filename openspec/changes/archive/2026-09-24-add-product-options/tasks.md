# Tasks

## 1. Backend: schema and entities

- [x] 1.1 Write Cycle migration creating `product_options` (`id`, `product_id` FK cascade, `name`, `selection_type` string, `required` boolean, `position`), `product_option_values` (`id`, `product_option_id` FK cascade, `name`, `price_delta` decimal default '0', `position`), and `order_item_options` (`id`, `order_item_id` FK cascade, `product_option_value_id` FK set-null nullable, `option_name`, `value_name`, `price_delta_snapshot` decimal). Verify with `docker exec docker-api-1 php bin/migrate.php` and confirm all three tables exist via `psql \dt`.
- [x] 1.2 Add `ProductOption`, `ProductOptionValue`, `OrderItemOption` Cycle entities under `src/Domain/Entity/` (non-final, annotation-based, matching `ProductIngredient`/`OrderItem` conventions). Verify Cycle schema compiles without errors (`docker exec docker-api-1 php bin/migrate.php` or the project's schema-cache command runs clean).
- [x] 1.3 Add repository interfaces under `src/Domain/Repository/` and Cycle implementations under `src/Infrastructure/Cycle/Repository/` for `ProductOption` (find-by-product) and confirm they're wired the same way `ProductIngredient`'s repository is (check existing bindings in DI config).

## 2. Backend: catalog management (business owner)

- [x] 2.1 Add `ProductService` methods to replace a product's full option-group set (group name, `selection_type`, `required`, ordered values with `price_delta`) in one call, mirroring `replaceIngredients`. Reject with a validation error when a non-zero `price_delta` is submitted for a product with no price. Add a Codeception Unit test exercising create/replace/reject-priceless-priced-option.
- [x] 2.2 Wire option-group payload into the existing product create/update controller actions under `src/Web/` (catalog management), and into `CatalogService`'s product serialization for the dashboard response. Verify via a Codeception Functional test hitting the product create/update endpoint with an options payload and asserting the response includes them.
- [x] 2.3 Update `CatalogService`'s public/dashboard product read path to include option groups/values in the returned structure (empty array when none exist, matching the `ingredients: []` pattern). Verify with a Unit/Functional test asserting a product with no options serializes with an empty `options: []`.

## 3. Backend: order intent

- [x] 3.1 Extend the `POST /public/orders` request DTO/validation to accept selected `product_option_value_id`s per submitted item.
- [x] 3.2 Add `OrderService` server-side validation: every submitted option value id belongs to a group on that item's product; every `required` `single` group has exactly one submitted value from its own values; a `single` group never receives more than one. Reject the whole order (no partial creation) on any violation. Add Codeception Unit tests for: valid selection, missing required selection, value from a different product/group, extra value on a `single` group.
- [x] 3.3 On successful validation, compute each item's unit price as base price plus selected `price_delta`s, persist `OrderItem` plus its `OrderItemOption` snapshot rows (`option_name`, `value_name`, `price_delta_snapshot` captured at creation time), and roll the adjusted line totals into `Order.total`. Add a Codeception Unit test asserting the persisted total reflects selected deltas.
- [x] 3.4 Update WhatsApp message generation to list each item's selected option values (name, price delta if non-zero) alongside its existing quantity/name/price/note line, and to use the delta-adjusted price. Add a Unit test asserting message formatting with and without selected options.

## 4. Frontend: dashboard catalog management

- [x] 4.1 Extend `apps/web/src/app/dashboard/catalog.models.ts` and `product-api.service.ts` with `ProductOption`/`ProductOptionValue` types and the option payload on product create/update requests.
- [x] 4.2 Add option-group editing UI to the product form in `dashboard/catalog-management/` (add/remove/reorder groups and values, set `selection_type`, `required`, `price_delta` per value), each component in its own `.component.ts`/`.html`/`.css` files per repo convention. Verify by running the app (`npm start`), opening a product's edit form, adding a group with values, saving, and confirming it reloads with the same data.
- [x] 4.3 Add/update a spec file for the product form covering: adding a group, adding a value with a price delta, and the priced-option-on-priceless-product client-side guard (mirrors the backend rule). Verify with `npx ng test --include='**/<the-spec-file>'`.

## 5. Frontend: public catalog and order flow

- [x] 5.1 Extend `apps/web/src/app/public/public.models.ts` with the option group/value shape returned by `GET /public/catalog/:slug`.
- [x] 5.2 Add option-selection UI to `public/catalog-page/` for a product with option groups (radio inputs for `single`, checkboxes for `multiple`), disabling "add to selection" while a `required` group is unselected. Verify by running the app, opening a catalog with an options-configured product (e.g. seed one on `cafe-vendaly`), and confirming selection is enforced.
- [x] 5.3 Update `cart.service.ts` and the order payload builder to carry selected option value ids per line item and include their `price_delta` in the displayed/computed subtotal and total. Add/update a spec (`cart.service.spec.ts`, `order-payload.spec.ts`) covering an item with selected options contributing to the total.
- [x] 5.4 Update `public/order-summary` display to list each item's selected option values. Verify by running the app end-to-end: add an item with options, submit the order, confirm the WhatsApp link/message preview shows the selections.

## 6. Verification

- [x] 6.1 Run the full backend suite (`docker exec docker-api-1 vendor/bin/codecept run Unit` and `Functional`) and confirm no regressions.
- [x] 6.2 Run the full frontend suite (`npm test`) and confirm no regressions.
- [ ] 6.3 Manually exercise the end-to-end flow in the browser: create a product with a required single-select group and a multi-select add-on group in the dashboard, publish the catalog, then as a customer select options, submit an order, and confirm the WhatsApp message reflects the selections and adjusted price.
