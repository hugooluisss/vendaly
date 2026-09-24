# Spec Delta

## Purpose

Defines the standardized, Tailwind-based visual styling contract that every screen in `apps/web` must follow, so form controls render consistently and the same brand look is reused everywhere instead of being reinvented per screen.

## ADDED Requirements

### Requirement: Consistent native form control rendering
Every native form control (`<select>`, checkbox, radio) rendered by the application SHALL display with consistent, non-broken styling (correct sizing, visible focus/checked state, aligned with adjacent labels) on every screen that contains one.

#### Scenario: Select rendered on a dashboard form
- **WHEN** a screen renders a `<select>` element (e.g. choosing a business category)
- **THEN** it renders with the same sizing, border, and focus treatment as text inputs on the same screen, with no unstyled or clipped native appearance

#### Scenario: Checkbox or radio rendered on a dashboard or public form
- **WHEN** a screen renders a checkbox or radio input (e.g. fulfillment method toggles, order status "Terminal"/"Predeterminado" controls)
- **THEN** its checked/unchecked and focus states are clearly visible and consistently sized across every screen that uses one

### Requirement: Standardized brand colors preserved
The application SHALL preserve its existing brand colors exactly as previously defined — the orange primary palette (`#EA580C` / `#F97316`), the neutral/slate/border/danger tokens, and the WhatsApp CTA green kept distinct from the orange brand color — with no color introduced or changed as part of restyling a screen.

#### Scenario: WhatsApp CTA button color is unchanged
- **WHEN** the "Enviar por WhatsApp" call-to-action is rendered anywhere in the app
- **THEN** its color is WhatsApp's official green, visually distinct from the orange brand primary, matching the color used before this change

#### Scenario: Orange brand primary is unchanged
- **WHEN** a primary button, active nav item, or other brand-colored element is rendered
- **THEN** its color matches the existing orange primary tokens, not a new or substituted color

### Requirement: Reusable standardized component classes
The application SHALL provide a single standardized set of classes for buttons, text/number/date inputs, selects, checkboxes/radios, and cards, and every screen SHALL use that shared set rather than defining its own one-off styling for the same kind of element.

#### Scenario: Two different screens render the same kind of control
- **WHEN** a primary button (or input, or card) appears on two different screens (e.g. "Guardar perfil" in business settings and "Nuevo producto" in catalog management)
- **THEN** both use the same standardized class(es) and render with the same look (padding, border, colors, states)

#### Scenario: A new screen is added after this change
- **WHEN** a screen is built after the standardized classes exist
- **THEN** it reuses the existing button/input/select/checkbox/card classes instead of introducing new one-off CSS for the same kind of element

### Requirement: No functional regression from restyling
Restyling a screen to use the standardized classes SHALL NOT change its functional behavior — form submission, validation, navigation, and data displayed remain the same as before the change.

#### Scenario: A restyled form still submits correctly
- **WHEN** a user fills out and submits a form on a screen that was restyled as part of this change (e.g. business profile settings)
- **THEN** the same fields are sent and the same success/error handling occurs as before the restyle
