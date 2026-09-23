# Tasks

## 1. Remove backend QR endpoint

- [x] 1.1 Delete `apps/api/src/Web/BusinessQr/Action.php` and `apps/api/src/Domain/Service/QrCodeService.php`, and verify no other file references `QrCodeService` or `BusinessQr` (`grep -rn "QrCodeService\|BusinessQr" apps/api/src`)
- [x] 1.2 Remove the `business.qr` route from `apps/api/config/common/routes.php` and verify the route list no longer contains `businesses/{id:\d+}/qr`
- [x] 1.3 Remove `testQrContainsTheExactPublicCatalogUrl` (and any now-unused imports) from `apps/api/tests/Unit/PublicCatalogAndOrdersTest.php`, and verify `docker exec docker-api-1 vendor/bin/codecept run Unit PublicCatalogAndOrdersTest` passes
- [x] 1.4 Remove `endroid/qr-code` from `apps/api/composer.json` and run `docker exec docker-api-1 composer update endroid/qr-code` to drop it from `composer.lock`, then verify `docker exec docker-api-1 vendor/bin/codecept run Unit` passes in full

## 2. Add client-side QR generation

- [x] 2.1 Add the `qrcode` npm package to `apps/web/package.json` and verify `npm install` succeeds (recreate the `web` container's node_modules volume if the stack is running in Docker)
- [x] 2.2 In `dashboard/business-settings/business-settings.component.ts`, compute the public catalog URL from the business slug via `Location.prepareExternalUrl(router.serializeUrl(router.createUrlTree(['/public/catalog', slug])))` prefixed with `window.location.origin`, exposed as a component property that is `undefined`/`null` when there is no slug
- [x] 2.3 Render that URL as text and, when present, render a QR code for it (via `qrcode`'s canvas API) in `business-settings.component.html`, and verify manually in the browser that the QR appears only once a business with a slug is loaded

## 3. Download

- [x] 3.1 Add a "Descargar" action next to the rendered QR that exports the canvas as a PNG file (`canvas.toDataURL('image/png')` + a synthetic anchor click), and verify manually that the downloaded PNG scans to the exact URL shown on screen
- [x] 3.2 Verify manually that the encoded URL is correct both with the stack served at the root and with `WEB_SERVE_FLAGS` set to the `gateway` configuration (path-prefixed reverse proxy)

## 4. Spec sync

- [x] 4.1 Verify `openspec validate --change add-business-qr-code-download --strict` passes
