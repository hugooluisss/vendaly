# Tasks

## 1. Component logic

- [ ] 1.1 Add a `share()` method to `CatalogPageComponent` that builds the canonical page URL (`window.location.origin + window.location.pathname`, no query params) and calls `navigator.share({ title: catalog.business.name, url })` when `typeof navigator.share === 'function'`; verify via a unit test in `catalog-page.component.spec.ts` that mocks `navigator.share` and asserts it's called with the expected title/url.
- [ ] 1.2 On `navigator.share` rejecting with `AbortError`, do nothing (no fallback, no toast); on any other rejection, or when `navigator.share` is not a function, call `navigator.clipboard.writeText(url)` and, on success, call `NotificationService.success(...)`; verify via unit tests covering: (a) unsupported browser falls back to clipboard + toast, (b) `AbortError` rejection results in no clipboard call and no toast, (c) a non-abort rejection falls back to clipboard + toast.

## 2. Template

- [ ] 2.1 Add a share icon `<button>` to the `business-contacts` nav in `catalog-page.component.html`, alongside the existing contact icons, always rendered (not conditional on any business field), calling `share()` on click; verify by running `npx ng test --include='**/catalog-page.component.spec.ts'` and confirming the button is present in the rendered template.

## 3. Verification

- [ ] 3.1 Run the full frontend test suite (`npm test` in `apps/web`) and confirm no regressions in `catalog-page.component.spec.ts` or elsewhere.
- [ ] 3.2 Manually smoke-test in a browser: confirm the share button appears next to the contact icons, that clicking it on a browser without `navigator.share` copies the URL and shows the toast, and that the copied URL matches the address bar (without the `?src=qr` marker when loaded via a QR scan).
