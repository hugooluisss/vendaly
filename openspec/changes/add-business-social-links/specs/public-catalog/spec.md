# Spec Delta

## ADDED Requirements

### Requirement: Public catalog exposes contact and social channels
The public catalog lookup response SHALL include the business's WhatsApp number, Facebook URL, Instagram URL, and website URL, each present as null when unset rather than omitted from the response.

#### Scenario: All channels set
- **WHEN** a visitor requests a published catalog whose business has a WhatsApp number, Facebook URL, Instagram URL, and website URL all set
- **THEN** the public response includes all four values

#### Scenario: Some channels unset
- **WHEN** a visitor requests a published catalog whose business has only some of the WhatsApp number, Facebook URL, Instagram URL, or website URL set
- **THEN** the public response represents each unset one as null rather than omitting the field

### Requirement: Catalog page renders only configured contact icons
The public catalog page SHALL render a contact icon linking to each of Facebook, Instagram, WhatsApp, and the business's website only when the corresponding value is set, and SHALL omit the icon entirely (not disabled or empty) when it is not.

#### Scenario: All four channels configured
- **WHEN** a visitor loads the catalog page for a business with a WhatsApp number, Facebook URL, Instagram URL, and website URL all set
- **THEN** the page renders all four contact icons, each linking to its respective channel

#### Scenario: Some channels not configured
- **WHEN** a visitor loads the catalog page for a business missing one or more of the Facebook URL, Instagram URL, or website URL
- **THEN** the page renders only the icons for the channels that are set, with no placeholder or disabled icon for the missing ones

#### Scenario: WhatsApp icon links via the business's phone number
- **WHEN** a visitor loads the catalog page for a business with a WhatsApp number set
- **THEN** the WhatsApp icon links to a `wa.me` URL built from that same number, without requiring or reading any separate WhatsApp-specific field
