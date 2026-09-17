## Why

Several agents built Angular components with inline `template:`/`styles:` strings inside the `@Component` decorator (a fast pattern to write but hard to read/diff once a component grows), and even the components that already externalized their template/styles into `.html`/`.css` files sit as loose files in a shared folder (e.g. `dashboard/catalog-management.component.ts` + `.html` + `.css` next to a dozen other components' files) rather than each in its own folder. This is a pure code-organization change with no behavior change — it makes every component's three concerns (view, styles, controller) easy to find and diff, matching a convention the project should have used from the start.

## What Changes

- Every Angular component gets its own folder, named after the component (kebab-case, matching the existing file-naming convention), containing exactly three files: `<name>.component.ts` (class only, no inline template/styles), `<name>.component.html` (view), `<name>.component.css` (styles, even if currently empty/minimal).
- Components currently using inline `template:`/`styles:` (found in the audit: `shared/modal`, `shared/image-upload`, `shared/map`, `dashboard/onboarding`, `dashboard/dashboard-home`, `dashboard/business-settings`, `public/public`, `dashboard/auth`) get their template/styles extracted into the new `.html`/`.css` files.
- Components already using `templateUrl`/`styleUrl` (`dashboard/order-history`, `dashboard/dashboard`, `dashboard/catalog-management`, `public/order-summary`, `public/catalog-page`) get moved into their own folder, with import paths updated everywhere they're referenced.
- No behavior change: this is a pure refactor. `skip_specs: true` is set on this change — no spec describes file layout, since file layout isn't externally observable behavior.

## Capabilities

(none — pure refactor, no spec-level behavior changes; `skip_specs: true`)

## Impact

- **Affected**: every `*.component.ts` under `apps/web/src/app/`, plus every import path that references them (routes files, other components that import them, `app.config.ts` if applicable).
- **Not affected**: apps/api, any backend behavior, any spec/requirement — this is Angular file organization only.
