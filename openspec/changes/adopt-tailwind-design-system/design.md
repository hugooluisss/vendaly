# Design

## Context

See `proposal.md` - Why. Technical starting points that shape this design:

- `apps/web` uses Angular 19's `application` builder (`@angular-devkit/build-angular:application`, esbuild-based) — this builder auto-detects a `tailwind.config.js` at the project root and wires PostCSS automatically; no manual `angular.json` PostCSS configuration is needed.
- Brand tokens today live as CSS custom properties in `src/styles.css` (see that file): `--vendaly-orange`, `-orange-light`, `-orange-soft`, `-slate`, `-muted`, `-border`, `-border-strong`, `-danger`, `-shadow`, `-shadow-soft`, `-surface`, `-bg`/`-background`, and `--whatsapp-green`/`-hover`/`-text`.
- Every component is standalone and keeps its own `.component.ts`/`.html`/`.css` triad; there is no shared component library today, only per-component CSS.
- Screens in scope: dashboard (`auth-form`, `onboarding`, `business-settings`, `catalog-management`, `dashboard` shell/nav, `dashboard-home`, `order-history`) and public (`directory`, `catalog-page`, `order-summary`, `order-checkout`, sharing `public/public.css`), plus shared components (`modal`, `map`, `image-upload`, `qr-code`).

## Goals / Non-Goals

**Goals:**
- Get Tailwind + `@tailwindcss/forms` building correctly in both `ng serve` and `ng build` (including the `gateway` configuration used behind the path-prefixed reverse proxy) and in Karma tests.
- Land a small, named set of standardized classes that every screen reuses for buttons, inputs (text/number/date), selects, checkboxes/radios, and cards.
- Migrate every screen to that shared set and fix visibly broken controls in the process.

**Non-Goals:**
- No new color values or palette changes (see spec: brand colors must be preserved exactly).
- No layout or information-hierarchy redesign.
- No change to component file structure or to Angular's reactive-forms/template-driven-forms usage — only presentation.
- No visual regression testing tooling (e.g. screenshot diffing) is introduced by this change; verification is manual per screen.

## Decisions

**Tailwind config location and content globs.** Put `tailwind.config.js` at `apps/web/` root (sibling to `angular.json`) so the `application` builder's auto-detection picks it up. `content` globs over `src/**/*.{html,ts}` so Tailwind sees classes used both in templates and in any `class` strings built in `.ts` files.

**Brand tokens as Tailwind theme colors, not left as CSS variables.** Port every `--vendaly-*`/`--whatsapp-*` custom property into `theme.extend.colors` in `tailwind.config.js` (e.g. `vendaly.orange`, `vendaly.orange-light`, `whatsapp.green`, ...) so they're usable as Tailwind utilities (`bg-vendaly-orange`, `text-whatsapp-green`, etc.) with autocomplete and consistent naming. Alternative considered: keep the CSS custom properties in `:root` and reference them from Tailwind config via `var(--vendaly-orange)` — rejected because it adds an indirection layer for no benefit once Tailwind owns the utility classes; a straight port keeps one source of truth (`tailwind.config.js`) instead of two.

**`@tailwindcss/forms` in `class` strategy, not `base`.** Use the plugin's `class` strategy (`plugin({ strategy: 'class' })`) so native controls only pick up the plugin's reset when a component explicitly opts in (e.g. `class="form-select"`/`form-checkbox`), rather than globally overriding every native `<select>`/`<input type=checkbox>` in the app the moment the plugin is installed. This lets the standardized classes (below) apply the reset deliberately as part of adopting them, screen by screen, instead of an app-wide style flip on day one that could make quirks show up in bulk. Alternative considered: `base` strategy (automatic global reset) — rejected because it changes every native control's rendering the instant the plugin lands, before the audit/migration work happens, mixing "install Tailwind" with "restyle every screen" into one unreviewable moment.

**Standardized classes as custom Tailwind component classes via `@layer components` + `@apply`, defined once in `src/styles.css`.** E.g. `.btn`/`.btn-primary`/`.btn-secondary`, `.field-input`, `.field-select` (pairs with `@tailwindcss/forms`' `form-select`), `.field-checkbox`/`.field-radio`, `.card`. Each is a handful of Tailwind utilities composed with `@apply`, kept in one place so there is exactly one definition per kind of control. Alternative considered: a set of small Angular shared components (e.g. `<app-button>`) wrapping the markup — rejected for this change because it would touch every call site's markup structure (not just its classes) and several controls here are native form elements bound via Angular Forms (`formControlName`, `[(ngModel)]`) where wrapping in a component adds `ControlValueAccessor` plumbing that is out of scope for a styling-only change; plain CSS classes are a strict style-only substitution.

**Migration is additive-then-subtractive per screen.** For each screen: add Tailwind utility classes / standardized classes to the template, verify it renders correctly, then delete the now-unused rules from that screen's `.component.css` (or the shared `public.css` block for that screen) in the same task. Doing the deletion in the same task (not a separate cleanup pass) avoids a long tail of dead CSS accumulating across the migration.

**Migration order.** Foundation first (install, theme tokens, standardized classes with at least one real usage to prove them out), then dashboard screens (used constantly by the business owner, highest visible inconsistency today per the user's report), then public screens, then shared components last (touched by multiple already-migrated screens, so migrating them last avoids re-touching dependents).

## Risks / Trade-offs

- **[Risk]** Tailwind's `preflight` base reset changes default margins/font rendering on elements that today rely on the browser default or on `styles.css`'s minimal reset (`* { box-sizing: border-box }`, etc.), causing small unintended shifts on screens not yet migrated. → **Mitigation**: verify each already-shipped screen visually right after installing Tailwind (before any class migration), and adjust `styles.css` base rules if preflight conflicts; this is the first task in tasks.md specifically so it's caught before other work builds on it.
- **[Risk]** Karma/Jasmine component tests assert on classes or computed styles that change once a screen is migrated. → **Mitigation**: run the existing test suite after each screen's migration task, not just at the end, so a break is attributable to the task that caused it.
- **[Risk]** The `web` Docker service's anonymous `node_modules` volume goes stale after adding the new dev dependencies (documented existing gotcha in the repo's `CLAUDE.md`). → **Mitigation**: call out the documented recreate steps explicitly in the first task.
- **[Trade-off]** Choosing plain CSS classes over Angular components (see Decisions) means no compile-time enforcement that a screen used the standardized class instead of a one-off rule — enforcement is manual/code-review only. Accepted because introducing wrapper components was ruled out as out of scope for a styling-only change.
- **[Risk, discovered during implementation]** The original plan had task 1.3 delete the `:root` custom-property block (`--vendaly-*`/`--whatsapp-*`) as soon as Tailwind theme colors existed, on the assumption each screen's migration is independently ordered. That's wrong: CSS custom properties on `:root` cascade through the whole DOM regardless of Angular's emulated view encapsulation, so deleting them immediately broke every *not-yet-migrated* screen's colors (any `var(--vendaly-*)` reference in a `.component.css` that hadn't had its migration task run yet resolved to nothing) the moment task 1.3 landed, well before those screens' own migration tasks. → **Mitigation applied**: the `:root` block was restored in `styles.css` alongside the Tailwind theme/`@layer components` block, so both systems coexist during the migration — already-migrated screens use the standardized classes/`theme()` references, not-yet-migrated screens keep working off the old variables. Task 6 (Final verification) now includes deleting that restored `:root` block for good, once every screen has been migrated off raw `var(--vendaly-*)`/`var(--whatsapp-*)` usage. This also means: any screen-specific CSS rule written during migration that isn't one of the 5 standardized classes should reference `theme('colors.vendaly.<name>')`, not a raw hex literal and not (going forward) the `var(--vendaly-*)` custom property — `theme()` keeps `tailwind.config.js` as the single source of truth per the Decisions above, while the temporarily-restored `var(--vendaly-*)` names exist only as a bridge for screens not yet touched.

## Migration Plan

No runtime data migration; this is a frontend build/styling change. Rollout is the phased task list in `tasks.md` (foundation → dashboard → public → shared components), each phase independently reviewable and revertable via git since no phase depends on a later phase's specific classes (only on the foundation phase's shared class definitions existing).

**Rollback**: since each screen's migration is a self-contained commit/PR-sized task, reverting a single problematic screen's commit fully restores its prior `.component.css` without affecting other already-migrated screens.
