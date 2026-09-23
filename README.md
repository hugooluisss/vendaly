# Vendaly

Vendaly is a SaaS for digital catalogs/menus shared via a public URL/QR code. Customers browse a business's catalog, build a selection, and send it to the business via a WhatsApp deep link (`wa.me`) — not the WhatsApp Business API. This is not e-commerce/POS: no payments, inventory, delivery, or invoicing in scope. A user is one business in the current model (`BusinessMember` exists for future multi-business/roles support but no multi-tenancy logic is implemented yet).

## Repository layout

- `apps/web/` — Angular frontend
- `apps/api/` — PHP/Yii3 API
- `docker/` — local stack orchestration
- `openspec/` — spec-driven change planning

## Run locally

Copy `apps/api/.env.example` to `apps/api/.env` before the first run. Then, from the repo root:

```bash
docker compose -f docker/docker-compose.yml up -d --build
```

The API runs on `:8080`, web app on `:4200`, PostgreSQL on `:5432`, and MinIO on `:9000` (API) and `:9001` (console).

After the first build, install PHP dependencies once and run migrations:

```bash
docker exec -u root -w /app vendaly-api-1 composer install --no-interaction
docker exec vendaly-api-1 php bin/migrate.php
```

For a web app served under a URL path prefix behind a reverse proxy on a shared subdomain, optionally copy `docker/.env.example` to `docker/.env` and set `WEB_SERVE_FLAGS`. This local-only override is gitignored. Leave it unset to serve the app normally at the root path.
