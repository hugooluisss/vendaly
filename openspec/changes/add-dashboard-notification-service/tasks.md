# Tasks

## 1. Notification service

- [x] 1.1 Add `sweetalert2` to `apps/web/package.json` (`npm install sweetalert2` from `apps/web`) and, if the stack is running in Docker, recreate the `web` service's `node_modules` volume (`docker compose -f docker/docker-compose.yml down web && up -d --build --force-recreate -V web`); verify by confirming `apps/web/node_modules/sweetalert2` exists and `npm run build` succeeds. Verified: `npm run build` succeeds locally, and the running `web` container was recreated (`docker compose down web && up -d --build --force-recreate -V web` via `sg docker`) — `sweetalert2` confirmed present in the container's `node_modules` and it compiled cleanly.
- [x] 1.2 Create `apps/web/src/app/shared/notification.service.ts` (`@Injectable({ providedIn: 'root' })`) with `success(message: string): void` and `error(message: string): void`, implemented via SweetAlert2 (toast-style for success, modal for error — see design.md), with no other file importing `sweetalert2` directly; verify with a unit test spying on the SweetAlert2 call to confirm each method invokes it with the right icon/message.

## 2. Convert business-settings.component.ts

- [x] 2.1 Inject `NotificationService` into `BusinessSettingsComponent`, remove the shared `error` field, and update `saveProfile()` to call `notification.success('...')` on success and `notification.error(...)` on failure (using the server's message when present, matching today's fallback text); verify with a unit test asserting both outcomes call the notification service correctly.
- [x] 2.2 Convert the 5 payment-method methods (`addPaymentMethod`, `renamePaymentMethod`, `deletePaymentMethod`, `movePaymentMethod`, and their shared reorder path) to call `notification.error(...)` on failure, keeping each method's existing fallback message text; verify via the updated unit tests.
- [x] 2.3 Convert the 6 order-status methods (`addOrderStatus`, `renameOrderStatus`, `recolorOrderStatus`, `toggleOrderStatusTerminal`, `setDefaultOrderStatus`, `deleteOrderStatus`, `moveOrderStatus`) to call `notification.error(...)` on failure, keeping each method's existing fallback message text; verify via the updated unit tests.
- [x] 2.4 Remove the `error` binding from `business-settings.component.html` (the `@if (error) { <p class="error">... }` block) now that nothing sets it; verify by confirming the template no longer references a removed field (build/typecheck passes).
- [x] 2.5 Update `business-status-settings.component.spec.ts` to inject/provide a spy `NotificationService` instead of asserting on `result.error`, adjusting each existing assertion to check the appropriate `notification.success`/`notification.error` call; verify the updated spec file passes. Verified: passes (see 6.1).

## 3. Convert catalog-management.component.ts

- [x] 3.1 Inject `NotificationService` and convert `togglePublish()`'s failure path (including the coordinates-required special case) to call `notification.error(...)` instead of setting `publishError`; remove `publishError` and its template binding. Leave `productError` (the `hasInvalidPricedOptions` pre-submit validation guard) as inline field-level state, unconverted, per design.md's action-vs-validation distinction; verify with a unit test covering both the coordinates-required and generic publish-failure cases calling the notification service, and confirm `productError` still renders inline. Verified: passes, including a test confirming `productError` still renders inline without calling the notification service (see 6.1).

## 4. Convert order-history.component.ts

- [x] 4.1 Inject `NotificationService` and convert `changeStatus()`'s failure path to call `notification.error(...)`; leave the business/statuses/orders load-failure paths (`error` field) as inline page-state messages, unconverted, per design.md's distinction; verify with a unit test confirming `changeStatus` failure calls the notification service while load failures still set the inline `error` field. Verified: passes, including a dedicated test confirming load failures stay inline (see 6.1).

## 5. Convert auth.component.ts

- [x] 5.1 Inject `NotificationService` and convert `submit()`'s failure path to call `notification.error(...)`, removing the `error` field and its template binding (decide whether to keep a minimal inline "check your fields" cue for form validation separately from the auth-failure notification, if the template currently conflates the two); verify with a unit test confirming a failed login/register calls the notification service. Verified: passes (see 6.1).

## 6. Verification

- [x] 6.1 Run `npx tsc --noEmit -p tsconfig.app.json` and the full affected `ng test` run (auth, business-settings incl. `business-status-settings.component.spec.ts`, catalog-management, order-history, and the new `notification.service.spec.ts`) from `apps/web`, and confirm everything passes with real output (not just a claim). Verified with a real headless-Chrome run: `tsc --noEmit` clean; targeted specs 30/30 pass; the full `ng test` suite (minus the pre-existing, unrelated `auth.interceptor.spec.ts` flake reproduced identically on unmodified `main`) is 78/78. One bug found and fixed during review: the new `business profile save notifications` suite in `business-settings.component.spec.ts` didn't mock `BusinessApiService.getMine()`, which the component's constructor always calls — added the missing mock. Also fixed `notification.service.spec.ts`'s error-modal assertion, which expected an explicit `toast: false` key the implementation correctly never sets.
- [ ] 6.2 Manually verify in the running app: save the business profile successfully (see a success notification), trigger a save failure (e.g. disconnect network briefly or violate a server-side rule) and see an error notification, and confirm the previously-inline error text is gone from business-settings, catalog-management's publish flow, order-history's status-change flow, and the login form. Not yet done — needs a human/browser pass; `sweetalert2` is installed and the `web` container was rebuilt and recompiles cleanly, ready to test.
