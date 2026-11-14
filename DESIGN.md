---
name: Vũ Điệu Rừng Xanh — Indoor Living and Community Blog
description: An airy Floema-inspired botanical reference and living-room guide with an independent community blog.
colors:
  paper: "#f2efea"
  paperWarm: "#ebe7df"
  paperSoft: "#f8f6f2"
  hairline: "#e4ded3"
  white: "#ffffff"
  ink: "#241f21"
  inkSecondary: "#524945"
  muted: "#645a55"
  forest: "#042d2b"
  yellow: "#e9e778"
  coral: "#f76c46"
  celadon: "#bacfa3"
  blue: "#85a1c5"
  kraft: "#c6af88"
  primary: "#042d2b"
typography:
  display:
    fontFamily: "Be Vietnam Pro, sans-serif"
    fontSize: "clamp(2.55rem, 5.8vw, 5.25rem)"
    fontWeight: 500
    lineHeight: 1.03
    letterSpacing: "-0.036em"
  body:
    fontFamily: "Be Vietnam Pro, sans-serif"
    fontSize: "16px"
    fontWeight: 400
    lineHeight: 1.62
rounded:
  control: "12px"
  card: "6px"
  chip: "8px"
  drawer: "14px"
  row: "16px"
spacing:
  small: "12px"
  medium: "24px"
  section: "clamp(68px, 9vw, 136px)"
components:
  button-primary:
    backgroundColor: "{colors.forest}"
    textColor: "{colors.yellow}"
    rounded: "{rounded.control}"
    padding: "12px 20px"
---

# Landing Design

## Overview

Scope: `index.php`, `.site-header` overrides inside `css/landing.css`, and `js/landing.js`, with the existing Blog extension below. The landing page adopts Floema's warm ivory paper palette (`#f2efea`), deep charcoal ink (`#241f21`), deep forest green narrative band (`#042d2b`), and flour-yellow footer (`#e9e778`) while preserving non-commercial Vietnamese indoor-plant guidance and real local imagery. Other internal and admin pages keep their existing stylesheets.

## Colors

Warm ivory (`#f2efea`), warm sand (`#ebe7df`), and soft cream (`#f8f6f2`) alternate cleanly across split panels and architectural sections, framed by `#e4ded3` hairlines. Charcoal ink (`#241f21`) and stone (`#524945`) maintain `>= 4.5:1` contrast on ivory and yellow grounds. Deep forest green (`#042d2b`) anchors primary actions and the narrative showcase band with high-contrast flour yellow (`#e9e778`) text. Category accent pills use `#f76c46`, `#bacfa3`, `#85a1c5`, and `#c6af88` with `#241f21` ink. Source tokens are the `--fl-*` custom properties in `css/landing.css`.

## Typography

Be Vietnam Pro (`500` medium for display and headings, `400/500/600` for body and controls) provides clean geometric letterforms across Vietnamese diacritics. Display type uses tight negative tracking (`-0.036em` to `-0.025em`) and generous architectural line-height (`1.03–1.24`). Catalog counts and indices use `tabular-nums`. Native select controls preserve legible `0.9rem`–`16px` sizing without viewport zoom jumps.

## Layout

1. **Minimalist Perspective Hero**: Centered `<h1>` surrounded by 18 unwatermarked plant tiles radiating outward along 1-point perspective rays from the center vanishing point (faint & soft-focus near center, sharpening and scaling up toward edges), grounded by a baseline rule with live catalog species count and `#room-scene` scroll anchor. Zero central CTA buttons clutter the headline.
2. **Annotated Room Scene**: Full-bleed `indoor-room.png` stage with `01` structural rail, `Xem cây trong nhà` CTA orb-pill, bottom-left guide card, and three interactive `details.room-spot` hotspots (`01`–`03`). Below `700px`, opened notes expand cleanly in document flow beneath the photograph.
3. **Scroll-Lit Manifesto & Blog**: Editorial inset photograph alongside multi-phrase statement (illuminating from `#645a55` to `#241f21` as readers scroll, defaulting to full `#241f21` under reduced motion / no-JS), followed by two curated journal posts.
4. **Live Light & Position Finder**: Compact GET form predicting matches in place across a 2x2 architectural showcase grid (`#ebe7df` / `#f8f6f2`) before linking to `plant-advisor.php`.
5. **Dappled Leaf Shadows & Category Directory**: Giant inline typography rows interspersed with upright thumbnails and live database counts over blurred botanical shadow silhouettes.
6. **Forest Narrative Band**: `#042d2b` surface with `#e9e778` flour-yellow typography, portrait plant inset, advisor summary, and tall right-hand specimen frame.
7. **Staggered Care Showcase & Closing Invitation**: Three stepped care columns (`tall` / `mid` / `short` aspect ratios) and bold catalog invitation (`collection.php`).
8. **Flour-Yellow Architectural Footer**: `#e9e778` ground with large botanical mark, 3 navigation columns, colophon, and `1.4px` botanical line-art SVGs.

## Elevation & Depth

Sections stay predominantly flat using hairline borders and value shifts rather than heavy drop shadows. Depth comes from the continuous 1-point perspective radial bloom in the hero, subtle scroll/pointer horizon shift (`--hero-travel`, `--hero-mx`, `--hero-my`), drifting dappled leaf shadows (`--shadow-y`), and desktop `.room-plant-note` cards (`0 20px 44px rgba(36, 31, 33, .2)`). Everything respects `prefers-reduced-motion: reduce` by freezing perspective tiles cleanly around the perimeter and disabling transitions and keyframe animations.

## Shapes

UI pills, header links, inputs, and buttons share Floema's signature `12px` radius (`--fl-pill-radius`), category badges use `8px`, and plant specimen/editorial photo cards use sharp `6px` corners (`--fl-card-radius`). Room markers are clean `38px` circular badges with high contrast.

## Components

- **Adaptive Sticky Navbar**: Uses shared `includes/navbar.php` DOM (plus landing search icon pill), shifting from ivory to `#042d2b` (`.is-dark`) over `.forest-highlight` and `#e9e778` (`.is-yellow`) over `.floema-footer`.
- **Room Disclosures**: Native `<details name="room-plants">` with JS exclusive selection, `Escape` dismissal restoring `<summary>` focus, outside click close, and zero horizontal/vertical clipping from `320px` to `1920px`.
- **Plant Finder**: Native GET form with live client-side preview filter and empty-state recovery, fully usable without JavaScript.

## Do's and Don'ts

- Preserve non-commercial Vietnamese editorial tone; never imply e-commerce checkout or delivery.
- Avoid placing eyebrow/kicker labels directly above section headings.
- Keep all landing overrides scoped inside `.landing-page` so shared internal/admin pages remain unchanged.
- Always honor `prefers-reduced-motion: reduce`, keyboard navigation, and live DB / fixture fallbacks.

## Blog Extension

The user prefers a Reddit-style community blog. `css/blog.css` loads only for `blog.php` and `blog-post.php`: a pale green-gray ground, white post surfaces, deep green actions and Be Vietnam Pro for headings, body text and controls. The landing also uses Be Vietnam Pro, with its own display scale and route-scoped styling. Existing post content and uploaded covers remain authoritative; the shared `blog_cover_url()` helper supplies existing indoor-plant photographs when a cover is absent.

The introduction and existing moving article strip occupy separate desktop columns. Preserve its pause, keyboard, touch and reduced-motion behavior. Posts always form one vertical feed, with author/date metadata first, compact right-hand thumbnails and up/down vote, save and comment controls. A topic sidebar sits to the right above 1050px and follows the feed below that breakpoint. Post surfaces use a 1px border and 12px corners without shadows. Search uses persistent labels and personal views use underlined tabs. Article text stays within 720px, and uploaded detail images use `object-fit: contain`. Use 44px action targets and 16px form text. Do not extend these route-specific styles to admin or other internal pages.
