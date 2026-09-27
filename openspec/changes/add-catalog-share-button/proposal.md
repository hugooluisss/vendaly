# Proposal

## Why

The public catalog page already shows contact icons (Facebook, Instagram, WhatsApp, website) but gives a visitor no direct way to share the store's own catalog URL with someone else — they'd have to copy it manually from the browser's address bar. A one-tap share affordance makes it easier for customers to pass the catalog along (e.g. in a WhatsApp chat), which is the primary distribution channel this product already relies on.

## What Changes

- Add a "share" icon button to the `business-contacts` nav in the public catalog page header, alongside the existing contact icons.
- On click, use the Web Share API (`navigator.share`) when available, sharing the current page URL and the business name as title.
- When `navigator.share` is unavailable (desktop browsers without support), or fails for a reason other than the visitor dismissing the share dialog, fall back to copying the URL to the clipboard (`navigator.clipboard.writeText`) and show a brief confirmation via the existing `NotificationService` toast. Dismissing the native share dialog does nothing further (no fallback copy).
- No backend changes: the catalog page already has its own shareable URL (`/public/catalog/:slug`), so nothing new needs to be persisted or returned by the API.

## Capabilities

### New Capabilities

(none)

### Modified Capabilities

- `public-catalog`: adds a requirement that the catalog page offers a share action for its own URL, alongside the existing "renders only configured contact icons" requirement.

## Impact

- `apps/web/src/app/public/catalog-page/catalog-page.component.ts` — add a `share()` method.
- `apps/web/src/app/public/catalog-page/catalog-page.component.html` — add the share button/icon to `business-contacts`.
- Uses existing `apps/web/src/app/shared/notification.service.ts` for the copy-fallback confirmation.
- No API, database, or spec changes to `public-catalog`'s data contract — this is UI-only.
