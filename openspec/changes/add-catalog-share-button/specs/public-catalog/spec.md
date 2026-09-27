# Spec Delta

## ADDED Requirements

### Requirement: Catalog page offers a share action for its own URL
The public catalog page SHALL render a share button, alongside the contact icons, that lets a visitor share the current catalog page's URL. When the browser supports native sharing, the system SHALL invoke it with the page URL and the business name. When native sharing is unavailable or fails for a reason other than the visitor dismissing the share dialog, the system SHALL copy the page URL to the clipboard and show a confirmation to the visitor.

#### Scenario: Native share supported
- **WHEN** a visitor on a browser that supports native sharing taps the share button
- **THEN** the system invokes native sharing with the current catalog page URL and the business name as title

#### Scenario: Native share unavailable
- **WHEN** a visitor on a browser without native sharing support taps the share button
- **THEN** the system copies the current catalog page URL to the clipboard and shows a confirmation to the visitor

#### Scenario: Native share dialog dismissed by the visitor
- **WHEN** a visitor opens the native share dialog and dismisses it without choosing a target
- **THEN** the system takes no further action and does not copy the URL or show a confirmation

#### Scenario: Native share fails for a reason other than dismissal
- **WHEN** native sharing is invoked and fails for a reason other than the visitor dismissing the dialog
- **THEN** the system falls back to copying the current catalog page URL to the clipboard and shows a confirmation to the visitor
