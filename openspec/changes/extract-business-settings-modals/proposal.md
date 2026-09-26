# Proposal

## Why

`BusinessSettingsComponent` currently owns four unrelated concerns in one file: the business profile form, fulfillment-methods settings (pickup/delivery/dine-in + fees), full payment-methods CRUD (add/rename/reorder/delete), and full order-statuses CRUD (add/rename/recolor/toggle-terminal/set-default/reorder/delete). This makes the component large and harder to change safely — a payment-method bug fix risks touching profile-form code by proximity, and vice versa. The component already has a precedent for splitting a concern into its own modal (the "Editar horarios" button + hours modal); fulfillment methods, payment methods, and order statuses should follow the same pattern as their own components.

## What Changes

- Extract payment-methods management (list, add, rename, reorder, delete) from `business-settings.component.ts`/`.html` into a new, self-contained `payment-methods-modal` component that renders its own `<app-modal>` internally.
- Extract order-statuses management (list, add, rename, recolor, toggle-terminal, set-default, reorder, delete) into a new, self-contained `order-statuses-modal` component, same pattern.
- Extract fulfillment-methods settings (pickup/delivery/dine-in toggles + fees) into a new, self-contained `fulfillment-methods-modal` component, same pattern, but with **one intentional behavior change**: today these fields are only persisted when the main "Guardar perfil" button is clicked (bundled into the same API call as the rest of the profile); the new modal gets its own "Guardar" button and saves independently via the same `BusinessApiService.update()` call, sending only the fulfillment fields. This was explicitly requested (rather than keeping it a memory-only edit synced back to the parent's save) so the modal behaves consistently with payment-methods/order-statuses, which already save independently of the profile form.
- `BusinessSettingsComponent` keeps only the profile form and hours modal, plus three new trigger buttons ("Editar métodos de pedido" / "Editar métodos de pago" / "Editar estados de pedidos") that open the new components as modals — mirroring the existing "Editar horarios" button. `saveProfile()` no longer includes fulfillment fields in its payload, since they're now saved independently.
- Existing test coverage for fulfillment/payment-method/order-status behavior (currently living oddly in `business-settings.component.spec.ts` and `business-status-settings.component.spec.ts`) moves to new spec files alongside the new components — no coverage is dropped.
- **No backend changes.** Every `BusinessApiService` method used today (`update` with fulfillment fields, `addPaymentMethod`, `renamePaymentMethod`, `deletePaymentMethod`, `reorderPaymentMethods`, `addOrderStatus`, `renameOrderStatus`, `recolorOrderStatus`, `setOrderStatusTerminal`, `setOrderStatusDefault`, `deleteOrderStatus`, `reorderOrderStatuses`) is called identically (same endpoint, same field shape) from the new components — `update()`'s existing type signature already accepts fulfillment fields on their own.
- **No observable behavior change** for payment methods or order statuses. For fulfillment methods, the one behavior change described above (independent save) is intentional and user-requested.

## Capabilities

This is a pure frontend implementation-detail refactor: no capability's requirements change (payment-method and order-status management behavior, as described in `business-management`, is unchanged — only where its UI code lives changes). This change sets `skip_specs: true` in `.openspec.yaml`, matching the existing `angular-component-file-structure` change, and carries no `specs/` delta.

## Impact

- **Frontend only**: `apps/web/src/app/dashboard/business-settings/business-settings.component.ts`/`.html` (shrinks further, adds three modal triggers, `saveProfile()` payload no longer includes fulfillment fields), three new component folders under `apps/web/src/app/dashboard/business-settings/` (`fulfillment-methods-modal/`, `payment-methods-modal/`, `order-statuses-modal/`), and their moved/adapted spec files.
- **No API, entity, or migration changes** — `BusinessApiService.update()` already accepts fulfillment fields as a standalone partial update.
- **Test coverage**: `business-settings.component.spec.ts`'s payment-method and fulfillment-fee tests, and all of `business-status-settings.component.spec.ts`, move to the new components' spec files; `business-settings.component.spec.ts` keeps only what still applies to it (profile save, QR code).
