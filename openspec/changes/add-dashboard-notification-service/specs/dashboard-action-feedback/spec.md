# Spec Delta

## Purpose

Gives the business owner a clear, consistent success or error notification after dashboard actions that save, change, or delete their data, so they always know whether an action actually took effect.

## ADDED Requirements

### Requirement: Success notification on business profile save
The system SHALL show a success notification to the business owner when the business profile save completes successfully.

#### Scenario: Profile save succeeds
- **WHEN** the business owner submits the business profile form and the save request succeeds
- **THEN** a success notification is shown confirming the save

### Requirement: Error notification on failed dashboard actions
The system SHALL show an error notification to the business owner when a dashboard action that creates, updates, deletes, or publishes their data fails, describing what went wrong when the server provides a specific reason.

#### Scenario: Business profile save fails
- **WHEN** the business owner submits the business profile form and the save request fails
- **THEN** an error notification is shown instead of the save silently doing nothing

#### Scenario: Payment method or order status action fails
- **WHEN** the business owner adds, renames, deletes, or reorders a payment method or order status and the request fails
- **THEN** an error notification specific to that action is shown

#### Scenario: Publish or unpublish fails
- **WHEN** the business owner tries to publish or unpublish their business and the request fails
- **THEN** an error notification is shown, including the coordinates-required reason when that is why it failed

#### Scenario: Order status change fails
- **WHEN** the business owner changes an order's status and the request fails
- **THEN** an error notification is shown and the order's displayed status is left unchanged

### Requirement: Notification mechanism is abstracted from its implementation
The system SHALL route all dashboard action success/error notifications through a single shared service, so the underlying presentation technology can be replaced without changing the components that trigger notifications.

#### Scenario: A component triggers a notification
- **WHEN** a dashboard component needs to show an action's success or error outcome
- **THEN** it calls the shared notification service rather than rendering its own alert UI or depending on a specific notification library directly
