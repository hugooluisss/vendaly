# Design

## Context

Four dashboard components currently manage their own ad-hoc `error`/`publishError`/`productError` string fields, set on HTTP `error` callbacks and rendered via a `@if (error) { <p class="error">{{ error }}</p> }`-style block in each template (see proposal.md - Why). None of them show anything on success. `business-settings.component.ts` reuses a single `error` field across ~11 unrelated methods (profile save, 5 payment-method operations, 6 order-status operations), so an error from one action can still be showing when a different action succeeds silently. `apps/web/package.json` has no toast/alert library; `qrcode` is the only comparable small UI-utility dependency added so far (per `redesign-dashboard-home-ui`).

## Goals / Non-Goals

**Goals:**
- One shared, injectable service (`NotificationService`) that every dashboard component calls for save/create/update/delete/publish/login-submit outcomes.
- Zero direct SweetAlert2 references outside that service's implementation.
- Add success feedback where none exists today (this proposal's original trigger: business profile save).

**Non-Goals:**
- Converting page-load/data-fetch failure states that leave the view in a persistent empty/broken condition (see Decisions) — those stay as inline state, not toasts.
- Any backend change — all server error messages already exist; this only changes how they're surfaced.
- A generic "toast queue"/notification history feature — SweetAlert2's default single-modal-at-a-time behavior is sufficient for this app's action cadence.

## Decisions

- **`NotificationService` interface**: `success(message: string): void` and `error(message: string): void`, exported from `apps/web/src/app/shared/notification.service.ts`, `@Injectable({ providedIn: 'root' })`. Kept to two methods (not `info`/`warning`/etc.) because that's the full set of outcomes any current call site needs; extend later if a real need appears rather than speculatively.
- **SweetAlert2 as the implementation, encapsulated entirely inside the service.** Use `Swal.fire({ icon: 'success', title: message, toast: true, position: 'top-end', timer: 2500, showConfirmButton: false })` for `success()` and a similar non-toast (or toast, TBD at implementation — a centered modal reads better for errors the owner must acknowledge) `icon: 'error'` call for `error()`. No component imports `sweetalert2` directly — only `notification.service.ts` does. This is what makes swapping the library later a one-file change.
- **Action-result vs. persistent-state distinction** (what gets converted vs. what stays inline):
  - *Converted*: any message that reports the outcome of a single user-initiated action (click save/add/delete/publish/login) and would otherwise vanish or get silently overwritten. These map naturally to a transient notification.
  - *Not converted*: `order-history.component.ts`'s load failures for the business/statuses/orders lists. These aren't reporting "an action you just took failed" — they're describing why the page currently has no data to show, which needs to stay visible on screen (a toast that auto-dismisses would leave the owner looking at an empty order list with no explanation). Also not converted: `catalog-management.component.ts`'s `productError` for `hasInvalidPricedOptions`, which is a client-side pre-submit validation guard (no server round-trip occurred) shown next to the price/options fields it refers to — converting that to a modal would interrupt the owner mid-edit for a correctable input error, which inline field-level validation handles better.
  - This line is a judgment call, not a hard rule from the spec; if the user wants the load-failure states converted too in review, that's a small follow-up, not a redesign.
- **`business-status-settings.component.spec.ts`** currently builds a bare `BusinessSettingsComponent` instance via `Object.create(BusinessSettingsComponent.prototype)` and asserts on `result.error`. Once `error` is removed from that component, these tests switch to providing/injecting a spy `NotificationService` (e.g. via `TestBed` instead of the raw prototype construction, or by manually assigning a spy object to a private field if the test intentionally avoids `TestBed` — match whatever the existing test file's construction style turns out to need) and asserting `notification.error`/`notification.success` were called with the expected message.
- **npm dependency**: add `sweetalert2` (a recent 11.x release, compatible with Angular 19/TS 5.7 — pin the exact version already resolved by `npm install` at implementation time) to `apps/web/package.json`. Per this repo's documented Docker workflow, adding a package while the stack is running requires recreating the `web` service's anonymous `node_modules` volume (`docker compose -f docker/docker-compose.yml down web && up -d --build --force-recreate -V web`).

## Risks / Trade-offs

- [SweetAlert2 modals are visually heavier than a lightweight toast library] → Mitigation: use its `toast: true` mode for success (small, corner, auto-dismiss) and reserve a full modal only for errors the owner should consciously acknowledge; this is a presentation detail fully contained in `NotificationService`, adjustable later without touching callers.
- [Removing the shared `error` field from `business-settings.component.ts` touches ~11 call sites in one pass] → Mitigation: mechanical, one-line-per-method change (swap `this.error = X` for `this.notification.error(X)`); covered by updating the existing spec file's assertions rather than adding new test scaffolding.
- [The action-result vs. persistent-state line drawn above is subjective] → Mitigation: documented explicitly here and in the proposal so it's visible for review rather than an silent implementation choice.
