# Landing: index.php

Mode: Persuade.

THESIS: Seeing plants in a real room makes choosing a first indoor plant approachable.

OWN-WORLD: A bright, airy, minimal home corner. Warm ivory, charcoal text, olive actions and restrained terracotta care-step numbers, with pale wood, linen and green leaves in the photograph. Lora headings and Be Vietnam Pro body text. The user explicitly chose minimal, bright, airy, few objects.

STORY: Explore a room plant, describe your room's light and placement, inspect a short suggestion list, then follow the catalog for care notes. Newcomers can read practical first steps and community stories. Use direct section headings and end with a compact catalog text link and the live species count.

FIRST VIEWPORT: Separate editorial copy and room photograph in a 36% / 64% desktop grid (40% / 60% at 701–1050px). The headline and direct finder link sit on warm ivory beside the photograph. Three 44px numbered annotations (01–03) identify plants. At 700px and below, copy sits above the image and disclosures expand below it. No text overlays the photograph.

FORM: Native PHP/HTML, a landing-only CSS file, and vanilla JavaScript. Concept reference `seed:4fa5dba9de3d2b24`; the user's pinned room scene and minimal direction take priority. Built directly in the existing PHP page; no full-page image comp or approval record is implied.

## Interaction and motion

The room photograph is static: no parallax, entrance animation or marker pulse. Native `details` preserve keyboard and no-JavaScript access; JavaScript adds exclusive room selection, Escape dismissal and outside-click dismissal. The finder filters the four editorial starting points locally and announces counts. Without JavaScript, its controls stay hidden and all plant links remain visible. Retain visible focus and reduced-motion support for local feedback.

## Assets

`images/indoor-room.png` was generated and then edited with built-in ImageGen for the user's bright/minimal preference. Its exact final generation prompt is embedded in the PNG. `images/optimized/indoor-room.webp` is the browser delivery copy. The existing four catalog photographs remain unchanged. Lora comes from Google Fonts and ships with its SIL Open Font License.

## Verification boundary

`tests/landing-ui.cjs` supports live HTTP and an explicit template-fixture mode. Fixture mode replaces only `plant_catalog_statistics()` with a count from the seed fixture; it renders the actual PHP template and loads actual assets over HTTP. It verifies layout and interactions, not database availability or authenticated flows.
