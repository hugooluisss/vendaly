## 1. Shared components

- [x] 1.1 Move `shared/modal.component.ts` into `shared/modal/`, extracting its inline template/styles into `modal.component.html`/`modal.component.css`; update every import of `ModalComponent`
- [x] 1.2 Move `shared/image-upload.component.ts` into `shared/image-upload/`, extracting inline template/styles into their own files; update every import of `ImageUploadComponent`
- [x] 1.3 Move `shared/map.component.ts` into `shared/map/`, extracting inline template/styles into their own files; update every import of `MapComponent`

## 2. Dashboard components

- [x] 2.1 Move `dashboard/dashboard.component.ts` (+ existing `.html`/`.css`) into `dashboard/dashboard/`; update its route/import references
- [x] 2.2 Move `dashboard/dashboard-home.component.ts` into `dashboard/dashboard-home/`, extracting its inline template into `.html` (styles already externalized)
- [x] 2.3 Move `dashboard/business-settings.component.ts` into `dashboard/business-settings/`, extracting its inline template into `.html` (styles already externalized)
- [x] 2.4 Move `dashboard/catalog-management.component.ts` (+ existing `.html`/`.css`) into `dashboard/catalog-management/`
- [x] 2.5 Move `dashboard/order-history.component.ts` (+ existing `.html`/`.css`) into `dashboard/order-history/`
- [x] 2.6 Move `dashboard/onboarding.component.ts` into `dashboard/onboarding/`, extracting inline template/styles into their own files
- [x] 2.7 Move `dashboard/auth/auth.component.ts` into `dashboard/auth/auth-form/` (or an equivalent non-colliding name — `dashboard/auth` is already a folder for the auth *feature*, so the component needs a folder name that doesn't collide with it), extracting its inline template into `.html` (styles already externalized)

## 3. Public components

- [x] 3.1 Move `public/public.component.ts` into `public/directory/`, extracting its inline template into `.html` (styles already externalized)
- [x] 3.2 Move `public/catalog-page.component.ts` (+ existing `.html`/`.css`) into `public/catalog-page/`
- [x] 3.3 Move `public/order-summary.component.ts` (+ existing `.html`/`.css`) into `public/order-summary/`

## 4. App root

- [x] 4.1 Keep `app.component.ts` at the `app/` root; `app.config.ts` and `app.routes.ts` are also scaffolded there, while feature components use folders

## 5. Verification

- [ ] 5.1 Run `npm run build` and `npm test -- --watch=false --browsers=ChromeHeadless` after the full migration, confirm both green with no broken import paths
- [ ] 5.2 Grep the codebase for any remaining `template:` or `styles:` inline usage inside `@Component(...)` decorators (excluding trivial one-liner shared components that were never in scope, if any are deliberately left inline — call those out explicitly rather than leaving them silently inconsistent) to confirm the migration is complete
