# Proposal

## Why

Business owners have no way to get a scannable QR code for their public catalog URL — a common need for printing on tables, flyers, or storefronts. A backend QR endpoint (`GET /businesses/{id}/qr`, SVG via endroid/qr-code) already exists but is never called from the frontend, so the feature is effectively unshipped. Rather than wiring the frontend to that endpoint, this change replaces it with client-side generation, avoiding an extra authenticated round-trip and an unused server dependency for what is a pure function of a public URL string.

## What Changes

- **BREAKING**: Remove the backend endpoint `GET /businesses/{id}/qr`, its route, `App\Web\BusinessQr\Action`, `App\Domain\Service\QrCodeService`, its unit test coverage, and the `endroid/qr-code` composer dependency. Nothing in the frontend currently calls this endpoint, so removing it has no user-facing effect on its own.
- Add client-side QR code generation in Angular (new dependency, e.g. `qrcode`) that encodes the business's public catalog URL (`<origin+base-href>/public/catalog/<slug>`), computed from `window.location` plus the app's base href so it works correctly whether served at the root or behind the path-prefixed reverse proxy.
- Display the generated QR code in the business settings page (`dashboard/business-settings`), next to the business's public catalog URL, only when the business has a slug.
- Let the business owner download the displayed QR code as a PNG image.

## Capabilities

### New Capabilities
(none)

### Modified Capabilities
- `public-catalog`: Replace the "QR code for public catalog URL" requirement — currently describing a backend-generated, authenticated SVG endpoint — with client-side generation and display/download in the business settings page.

## Impact

- **Backend (`apps/api`)**: Remove `src/Web/BusinessQr/Action.php`, `src/Domain/Service/QrCodeService.php`, the `business.qr` route in `config/common/routes.php`, the corresponding test in `tests/Unit/PublicCatalogAndOrdersTest.php`, and the `endroid/qr-code` entry in `composer.json`/`composer.lock`.
- **Frontend (`apps/web`)**: Add a QR-generation npm dependency; update `dashboard/business-settings/business-settings.component.{ts,html}` to render and offer download of the QR code.
- No database or migration changes.
