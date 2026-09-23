# Design

## Context

A backend QR endpoint (`GET /businesses/{id}/qr`) already exists (`App\Web\BusinessQr\Action` + `App\Domain\Service\QrCodeService`, using `endroid/qr-code`, reading `PUBLIC_CATALOG_BASE_URL`) but nothing in the frontend calls it. See proposal.md - Why for why this change replaces it with client-side generation instead of wiring the frontend to it.

The app can be served at the root (`baseHref: "/"`, dev/default) or under a path prefix behind a reverse proxy (`baseHref: "/vendaly-app/"`, the `gateway` build configuration added in `f3e836d`). The public catalog URL construction must work under both without hardcoding a prefix.

## Goals / Non-Goals

**Goals:**
- Compute the public catalog URL correctly regardless of active base href.
- Generate and render the QR code entirely client-side, with no network round-trip.
- Offer a one-click PNG download of the exact QR code shown on screen.

**Non-Goals:**
- Customization (embedded logo, size/color options) — explicitly deferred per user decision.
- Any print-ready layout or dedicated "QR" page — it lives inline in business settings.
- SVG export — PNG only, per user decision.

## Decisions

- **Library**: use `qrcode` (npm), rendering to a `<canvas>` via its Angular-friendly `toCanvas`/`QRCodeModule` API. It's a small, actively maintained, dependency-free QR encoder with native canvas output, which gives PNG export for free via `canvas.toDataURL('image/png')` — no extra image-conversion step needed. Alternative considered: `angularx-qrcode` (a wrapper around the same `qrcode` library) — passed on to avoid an extra layer for a single usage site.
- **URL construction**: build the URL with Angular's `Location.prepareExternalUrl(router.serializeUrl(router.createUrlTree(['/public/catalog', slug])))` prefixed with `window.location.origin`, rather than string-concatenating a hardcoded `/public/catalog` path. This automatically respects whichever `baseHref` the app was built with (root or `/vendaly-app/`), matching how the rest of the app already navigates to public catalog routes (e.g. `directory/public.component.ts`'s `router.navigate(['/public/catalog', slug])`).
- **Rendering location**: inline in `business-settings.component.{ts,html}`, gated on `business.slug` being present, next to where the public URL is (newly) displayed as text — no new route or component needed given it's a single small feature.
- **Backend removal**: delete `Action.php`, `QrCodeService.php`, the `business.qr` route, its unit test, and the `endroid/qr-code` composer dependency in the same change, since nothing depends on them and leaving dead, untested-by-removal code around is worse than a clean removal.

## Risks / Trade-offs

- [Removing the backend endpoint is a breaking API change] → No frontend or known external consumer calls it (verified via repo-wide search); safe to remove in this change rather than deprecate-and-wait.
- [`window.location.origin` differs between local dev, docker, and the public gateway domain] → This is intentional: the QR must encode whatever origin the owner is currently viewing the dashboard from, which is the same origin the public catalog is actually reachable at in that environment.

## Migration Plan

- Remove backend files/route/test/dependency, run `composer update` inside the API container to drop `endroid/qr-code` from `composer.lock`, and run the full backend suite to confirm nothing else referenced it.
- Add the frontend dependency, implement the component changes, and manually verify the QR scans correctly to the right URL in both root and `gateway` (`WEB_SERVE_FLAGS`) serving modes.
- No data migration; no rollback concerns beyond reverting the commit.
