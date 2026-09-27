# Design

## Context

`catalog-page.component.html`'s `business-contacts` nav already conditionally renders Facebook/Instagram/WhatsApp/website icons as `<a>` elements based on which fields are set on `catalog.business`. See proposal.md for motivation.

## Goals / Non-Goals

**Goals:**
- Add a share icon button to that same nav, always visible (not conditional on any business field — every published catalog has a URL).
- Prefer native OS share sheet on supported browsers; degrade gracefully elsewhere.

**Non-Goals:**
- No dedicated share-count/analytics tracking (out of scope; the existing scan-tracking capability only covers QR scans).
- No custom share-target list (e.g. explicit "share to WhatsApp" button) — deferred; native share sheet already surfaces the visitor's own apps.

## Decisions

- **Feature detection via `typeof navigator.share === 'function'`** rather than a UA/platform check, so the button's behavior tracks actual browser capability.
- **Distinguish user-dismissal from failure**: `navigator.share()` rejects with `AbortError` when the visitor closes the share sheet without picking a target. That specific rejection is swallowed silently (no fallback, no error toast) — anything else (unsupported types, permission errors) falls back to clipboard copy. This matches how a native share sheet's cancel is expected to behave (no leftover toast).
- **Clipboard fallback reuses `NotificationService.success(...)`**, the same toast service already used in the dashboard, for the "copiado" confirmation — no new UI component needed.
- **URL source**: build from `window.location.href` at click time rather than reconstructing the slug-based path, so any query params already on the page (e.g. `?src=qr`) aren't carried into what's shared — share the canonical page URL without tracking params: `${window.location.origin}${window.location.pathname}`.

## Risks / Trade-offs

- [`navigator.clipboard.writeText` requires a secure context (HTTPS) and may reject in some embedded webviews] → Mitigation: on rejection, no crash; consider it an acceptable degraded case (button simply doesn't confirm), consistent with how the app already treats non-critical browser API failures elsewhere (e.g. `catchError` around catalog fetch).
