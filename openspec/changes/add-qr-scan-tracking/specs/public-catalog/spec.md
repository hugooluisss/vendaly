# Spec Delta

## MODIFIED Requirements

### Requirement: QR code for public catalog URL
The system SHALL generate a QR code encoding the business's public catalog URL with a scan-source marker appended as a query parameter, retrievable by the authenticated business owner, so that catalog page loads originating from the QR code can be distinguished from direct link visits.

#### Scenario: Generate QR code
- **WHEN** the business owner requests the QR code for their published catalog
- **THEN** the system returns an image encoding the public catalog URL for their slug with the scan-source marker included
