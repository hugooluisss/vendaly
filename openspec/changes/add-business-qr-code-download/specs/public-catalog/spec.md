# Spec Delta

## MODIFIED Requirements

### Requirement: QR code for public catalog URL
The system SHALL let the business owner view and download a QR code encoding the exact public catalog URL for their business, generated client-side from the business's slug — without a dedicated backend endpoint.

#### Scenario: View QR code for a published, slugged business
- **WHEN** the business owner has a business with a slug
- **THEN** the business settings page renders a QR code encoding the public catalog URL for that slug

#### Scenario: Download the QR code
- **WHEN** the business owner requests to download the displayed QR code
- **THEN** the system provides it as a PNG image file encoding the same public catalog URL shown on screen

#### Scenario: No business or no slug yet
- **WHEN** the business owner has no business, or their business has no slug
- **THEN** the business settings page does not render a QR code

#### Scenario: Generate QR code
- **WHEN** the business owner requests the QR code for their published catalog
- **THEN** the system returns an image encoding the exact public catalog URL for their slug
