## Why

`business-geolocation` made coordinates optional, deliberately, since geolocation was itself an add-on to the original text-only `location` field. Now that the directory's proximity search and map depend on coordinates to be useful, an unpinned business is invisible to "nearest to me" search even though it's fully published and browsable otherwise. The owner explicitly wants coordinates required before a catalog can go live, so every published business is guaranteed to participate in proximity search.

## What Changes

- Publishing a catalog now requires the business to already have coordinates (latitude/longitude) set. Attempting to publish without them is rejected with a clear error telling the owner to set their location on the map first.
- Unpublishing is unaffected — a business can still unpublish regardless of whether it has coordinates.
- A business that already has coordinates and is already published is unaffected — this only gates the *action* of publishing, not existing published state.
- The business profile form (dashboard) reflects this: the "Publicar catálogo" action is disabled (or clearly blocked) in the UI until the owner has placed a pin, so the rejection is never a surprise the owner discovers only after clicking publish.

## Capabilities

### Modified Capabilities
- `catalog-management`: publishing now requires coordinates to be set.

## Impact

- **Backend**: `BusinessService::setPublished()` (or wherever publish is implemented) adds a precondition check — coordinates must be non-null before allowing `is_published = true`.
- **Frontend**: the dashboard's "Publicar catálogo" button reflects this precondition (disabled/hinted when coordinates are missing), in `catalog-management.component` (where the publish toggle lives) and/or `business-settings.component` (where coordinates are set).
- No change to the public catalog/directory endpoints themselves, or to businesses that are already published.
