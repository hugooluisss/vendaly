# Proposal

## Why

`apps/web` has no CSS framework and no shared class system: each component ships its own hand-rolled `.component.css`, so native form controls (`<select>`, checkboxes, radios) render inconsistently across screens — some look fine, others visibly break — and there is no standardized set of button/input/card classes to keep new screens consistent with existing ones. Adopting Tailwind CSS (with the `@tailwindcss/forms` plugin) gives the app a consistent utility layer and a normalized baseline for native form controls, and lets us define a small set of standardized component classes once instead of reinventing them per screen.

## What Changes

- Add Tailwind CSS (and `@tailwindcss/forms`) to `apps/web`'s build, configured to scan all component templates.
- Port the existing brand tokens from `src/styles.css` (`--vendaly-orange`/`-light`/`-soft`, `--vendaly-slate`, `--vendaly-muted`, `--vendaly-border`/`-strong`, `--vendaly-danger`, `--vendaly-shadow`/`-soft`, `--vendaly-surface`, `--vendaly-bg`/`-background`, `--whatsapp-green`/`-hover`, `--whatsapp-text`) into Tailwind's `theme.extend.colors` verbatim — no new colors, no palette changes.
- Introduce a small set of standardized, reusable classes (buttons, text/number/date inputs, selects, checkboxes/radios, cards) built from Tailwind utilities (via `@apply` or component classes), replacing the ad hoc per-component rules for these same elements.
- Audit and update every screen (dashboard and public) to use the standardized classes and fix whatever renders broken today (selects and checkboxes are the known offenders; others may surface during the audit).
- Remove now-redundant custom CSS in each `.component.css` as it's replaced by the standardized classes and Tailwind utilities.
- **Not in scope**: no layout/information-hierarchy redesign, no new colors, no component file-structure changes (each component keeps its own `.component.ts`/`.html`/`.css`).

## Capabilities

### New Capabilities
- `web-design-system`: defines the standardized Tailwind-based styling system for `apps/web` — brand tokens as Tailwind theme colors, the shared button/input/select/checkbox/card classes, and the requirement that native form controls render consistently (non-broken) across every screen.

### Modified Capabilities
None. This change is presentation-only: it does not alter any existing capability's user-facing behavior, request/response contracts, or business rules — only how existing screens are styled.

## Impact

- **Affected code**: `apps/web` build config (`angular.json`, new `tailwind.config.js`/`postcss.config.js`), `apps/web/src/styles.css` (brand tokens move into Tailwind theme, base file becomes Tailwind's `@tailwind` directives + minimal globals), and every `*.component.html`/`*.component.css` under `apps/web/src/app` (dashboard: `auth-form`, `onboarding`, `business-settings`, `catalog-management`, `dashboard`, `dashboard-home`, `order-history`; public: `directory`, `catalog-page`, `order-summary`, `order-checkout`, shared `public.css`; shared: `modal`, `map`, `image-upload`, `qr-code`).
- **Dependencies**: adds `tailwindcss`, `@tailwindcss/forms`, `postcss`, `autoprefixer` as dev dependencies in `apps/web/package.json`. If the `web` Docker service's `node_modules` volume is stale after this, it needs the recreate steps already documented in the repo's `CLAUDE.md`.
- **No backend/API impact.**
