# Proposal

## Why

Dashboard actions (saving the business profile, publishing, managing payment methods/order statuses/products, logging in, updating an order's status) give the owner no positive confirmation when they succeed, and only ever fail silently into a small inline `error` string — several components even share one field across many unrelated actions, so a stale error from a previous action can linger on screen. The owner has no reliable way to tell "did my save actually work?" This proposal adds a single, consistent notification mechanism for action feedback (success and failure) across the dashboard.

## What Changes

- Add a `NotificationService` (`apps/web/src/app/shared/notification.service.ts`) with a small technology-agnostic interface — `success(message: string)` and `error(message: string)` — so calling components never reference the underlying UI library directly and it can be swapped later without touching any caller.
- Implement it using SweetAlert2 (new npm dependency for `apps/web`) as a lightweight, dismissible toast/alert.
- Convert every existing action-triggered inline error field tied to a save/create/update/delete/publish/login action to call `NotificationService.error(...)` instead of setting local state, and add a `NotificationService.success(...)` call on the corresponding success path where none exists today:
  - `dashboard/auth/auth-form/auth.component.ts` — login/register submit failure.
  - `dashboard/business-settings/business-settings.component.ts` — profile save (add explicit success message here per the triggering request), payment method add/rename/delete/reorder, order status add/rename/recolor/toggle-terminal/set-default/delete/reorder (all currently share one `error` field).
  - `dashboard/catalog-management/catalog-management.component.ts` — publish/unpublish failure (including the coordinates-required message).
  - `dashboard/order-history/order-history.component.ts` — order status change failure.
- **Not converted, kept as inline/local UI state** (these are not one-off action results, they describe a persistent condition of the current view or block a submit before any request is made — see design.md for the full rationale):
  - `order-history.component.ts`'s data-load failures (business/statuses/orders failed to load) — these leave the page in an empty/broken state that a transient toast would not adequately represent; the inline message stays as the description of that state.
  - `catalog-management.component.ts`'s `productError` for `hasInvalidPricedOptions` — a pre-submit client-side validation guard shown next to the form, not a server round-trip result.
- Update `business-status-settings.component.spec.ts` (tests `BusinessSettingsComponent`'s order-status methods via direct `.error` field manipulation) and any other spec relying on the fields being removed, to instead assert on `NotificationService` calls.
- **BREAKING** (internal only, not user-facing): removes the public `error`/`publishError` fields this proposal converts from the affected components; any other code reading them (none found outside their own templates/specs) would need updating.

## Capabilities

### New Capabilities
- `dashboard-action-feedback`: Business owners get a visible success or error notification after dashboard actions that save, change, or delete their data.

### Modified Capabilities
_None._ No existing capability's documented requirements change — this adds a new observable UI behavior (feedback on action outcome) rather than altering what any existing capability (business-management, catalog-management, etc.) does.

## Impact

- **Frontend only**: new `apps/web/src/app/shared/notification.service.ts` (+ spec), new `sweetalert2` npm dependency (`apps/web/package.json`/`package-lock.json`), and edits to the four components listed above plus their specs. No backend, no API, no database changes.
- Adding the npm dependency requires recreating the `web` service's `node_modules` volume when the stack is running in Docker, per this repo's documented workflow (`docker compose down web && up -d --build --force-recreate -V web`).
