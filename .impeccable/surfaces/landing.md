# Landing: index.php

Mode: Persuade.

THESIS: Seeing plants in a real room makes choosing a first indoor plant approachable.

OWN-WORLD: A bright, airy, minimal home corner. Off-white limewash, pale wood, linen and green leaves. Lora headings and Be Vietnam Pro body text. The user explicitly chose minimal, bright, airy, few objects.

STORY: Explore a room plant, describe your room's light and placement, inspect a short suggestion list, then follow the catalog for care notes. Newcomers can read practical first steps and community stories.

FIRST VIEWPORT: One photographic room with three selectable plants, a clear indoor-plant headline, and a direct link to the on-page finder. Mobile puts copy above the image and disclosures below it.

FORM: Native PHP/HTML, a landing-only CSS file, and vanilla JavaScript. Concept reference `seed:4fa5dba9de3d2b24`; the user's pinned room scene and minimal direction take priority. Built directly in the existing PHP page; no full-page image comp or approval record is implied.

## Assets

`images/indoor-room.png` was generated and then edited with built-in ImageGen for the user's bright/minimal preference. Its exact final generation prompt is embedded in the PNG. `images/optimized/indoor-room.webp` is the browser delivery copy. The existing four catalog photographs remain unchanged. Lora comes from Google Fonts and ships with its SIL Open Font License.

## Verification boundary

`tests/landing-ui.cjs` supports live HTTP and an explicit template-fixture mode. Fixture mode replaces only `plant_catalog_statistics()` with a count from the seed fixture; it renders the actual PHP template and loads actual assets over HTTP. It verifies layout and interactions, not database availability or authenticated flows.
