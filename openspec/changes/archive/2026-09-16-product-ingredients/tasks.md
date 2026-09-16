## 1. Backend data model

- [x] 1.1 Add migration creating `product_ingredients` (id, product_id FK → products, name, position) following the existing migration style; verify it applies cleanly against the running Postgres
- [x] 1.2 Add `ProductIngredient` entity and repository (interface + Cycle implementation) with `replaceForProduct(productId, names[])` (delete existing rows for that product, insert the submitted list in order) and `findByProductIds(ids[])` (batched, for the public catalog); verify a unit test covering replace-on-save (add, then replace with a different set, confirm only the new set remains)

## 2. Backend service/controller wiring

- [x] 2.1 Extend `ProductService::create()`/`update()` to accept an optional `ingredients: string[]` and persist them via `replaceForProduct`; verify unit tests cover creating with ingredients, creating without, and editing to change the list
- [x] 2.2 Extend `CatalogService::publicCatalog()` to batch-fetch ingredients for the active products already being returned and include `ingredients: string[]` (empty array, never omitted) per product; verify a unit test covers a product with ingredients, a product without, and confirms no N+1 (one ingredients query regardless of product count)
- [x] 2.3 Update `CatalogController`'s product create/update actions to read `ingredients` from the request body; the controller accepts the documented multipart JSON field and passes it through to the service

## 3. Frontend

- [x] 3.1 Extend `ProductInput`/`Product` models and `ProductApiService` to send/receive `ingredients: string[]`; verify a unit test on the service covers the new field
- [x] 3.2 Add an ingredients editor (add/remove chips, simple text input + list) to the product modal in `catalog-management.component`; verify a manual walkthrough adds, removes, and saves a product's ingredient list
- [x] 3.3 Show a product's ingredients (when present) on the public catalog product card in `catalog-page.component`; verify a manual load of a published catalog with an ingredient-bearing product

## 4. Verification

- [x] 4.1 Run the full backend test suite inside docker-api-1 and the Angular build/tests, confirm both green with the new ingredient fields in place
