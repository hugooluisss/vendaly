## Why

Vendaly needs a first working version of its core loop — a business owner publishes a catalog, a customer browses it without logging in, builds a selection, and sends it to the business via a WhatsApp deep link — before any post-MVP feature (variants, payments, analytics) can be layered on. This proposal establishes that foundation end to end, from account creation to the WhatsApp handoff, across an Angular frontend and a PHP/Yii 3 backend.

## What Changes

- Introduce user authentication (register/login) for business owners.
- Introduce business management: create a business, configure name, logo, WhatsApp number, and opening hours; generate a unique public slug.
- Introduce catalog management: categories, products/services with name, description, image, optional price, and active/inactive availability; publish/unpublish a catalog.
- Introduce a public, unauthenticated catalog view reachable at `GET /public/catalog/:slug`, mobile-first, showing only published/active content.
- Introduce order intent: a customer-side selection (cart) with quantities and notes, persisted via `POST /public/orders` before redirecting to a generated `wa.me` deep link with a formatted order message. Persisting the order does not imply payment or confirmation.
- Introduce QR code generation for a business's public catalog URL.
- Domain model must anticipate `ProductOption`/`ProductOptionValue` (variants/extras) without implementing them now — `Product` must not be designed in a way that blocks adding them later.
- Explicitly out of scope for this change: payment processing, inventory management, delivery logistics, kitchen management, invoicing, WhatsApp Business API integration, multi-branch/multi-catalog, analytics dashboard.

## Capabilities

### New Capabilities
- `user-auth`: business-owner registration and login (session/token issuance).
- `business-management`: creating/configuring a business (info, logo, WhatsApp number, opening hours, unique slug) and managing members' access to it.
- `catalog-management`: authenticated CRUD for categories and products/services, availability toggling, and publish/unpublish of a business's catalog.
- `public-catalog`: unauthenticated public read access to a published catalog by slug, and QR code generation for that URL.
- `order-intent`: customer-side selection/cart (items, quantities, notes), persisting an order pre-WhatsApp, and generating the formatted `wa.me` deep link.

### Modified Capabilities
(none — greenfield project, no existing specs)

## Impact

- **New backend app** (`apps/api`, PHP 8 / Yii 3): controllers → services → repositories layering, Cycle ORM entities/repositories, PostgreSQL schema for `User`, `Business`, `BusinessMember`, `Catalog`, `Category`, `Product`, `ProductImage`, `Order`, `OrderItem`.
- **New frontend app** (`apps/web`, Angular): authenticated dashboard (business/catalog management) and a separate mobile-first public catalog + cart experience.
- **New infra**: PostgreSQL, S3-compatible object storage for product/logo images, Docker Compose for local orchestration of `apps/web` + `apps/api` + Postgres.
- **No external integrations beyond `wa.me` deep links** — no WhatsApp Business API, no payment gateway.
