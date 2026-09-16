## Purpose

Lets a business owner create an account and authenticate, so that only they can manage their own business and catalog.

## Requirements

### Requirement: Account registration
The system SHALL allow a new user to register with an email and password, rejecting registration if the email is already in use.

#### Scenario: Successful registration
- **WHEN** a visitor submits a unique email and a valid password
- **THEN** the system creates a `User` record and returns access and refresh tokens

#### Scenario: Duplicate email
- **WHEN** a visitor submits an email already registered to another `User`
- **THEN** the system rejects the request without creating a record and returns a validation error identifying the email field

### Requirement: Login issues a token pair
The system SHALL authenticate a user by email and password and, on success, issue a short-lived access token and a longer-lived refresh token.

#### Scenario: Successful login
- **WHEN** a user submits their correct email and password
- **THEN** the system returns a valid access token and refresh token

#### Scenario: Invalid credentials
- **WHEN** a user submits an email/password combination that does not match a stored account
- **THEN** the system rejects the request without revealing whether the email exists

### Requirement: Access token required for authenticated endpoints
The system SHALL require a valid, unexpired access token on every request to an authenticated endpoint (business, catalog, category, and product management).

#### Scenario: Missing or expired access token
- **WHEN** a request to an authenticated endpoint carries no access token or an expired one
- **THEN** the system rejects the request with an unauthorized error

### Requirement: Refresh token exchange
The system SHALL allow a user holding a valid, unexpired refresh token to obtain a new access token without re-entering credentials.

#### Scenario: Valid refresh token
- **WHEN** a user submits a valid, unexpired refresh token
- **THEN** the system issues a new access token

#### Scenario: Expired or revoked refresh token
- **WHEN** a user submits an expired or revoked refresh token
- **THEN** the system rejects the request and the user must log in again
